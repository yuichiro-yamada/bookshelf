<?php

namespace App\Http\Controllers\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReadingPlan\ReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧を表示する（ログインユーザー自身の計画のみ）
     */
    public function index(Request $request): View
    {
        $currentStatus = $request->query('status');
        $statusFilter = ReadingPlanStatus::tryFrom((string) $currentStatus);

        // User::readingPlans() リレーション経由で、ログインユーザー自身の計画に限定する
        $query = Auth::user()->readingPlans()
            ->with('book')
            ->orderBy('target_date');

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter->value);
        }

        $readingPlans = $query->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画作成画面を表示する
     */
    public function create(): View
    {
        // すでに「進行中」の計画がある書籍は選択肢から除外する
        $activeBookIds = Auth::user()->readingPlans()
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->pluck('book_id');

        $books = Book::whereNotIn('id', $activeBookIds)
            ->orderBy('title')
            ->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画を作成する
     */
    public function store(ReadingPlanRequest $request): RedirectResponse
    {
        // 認可（ReadingPlanPolicy::create）はReadingPlanRequest::authorize()側で行っている
        $validated = $request->validated();

        $book = Book::findOrFail($validated['book_id']);

        // user_id は User::readingPlans() リレーション経由で自動的に設定される
        Auth::user()->readingPlans()->create([
            'book_id' => $book->id,
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::InProgress,
        ]);

        return redirect()->route('reading-plans.index')->with('success', "「{$book->title}」の読書計画を作成しました。");
    }

    /**
     * 読書計画編集画面を表示する
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        $this->authorize('update', $readingPlan);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画を更新する（期日の変更）
     */
    public function update(ReadingPlanRequest $request, ReadingPlan $readingPlan): RedirectResponse
    {
        // 認可（ReadingPlanPolicy::update）はReadingPlanRequest::authorize()側で行っている
        $validated = $request->validated();

        // target_date は「今日以降」しか許可していないため、更新後は必ず「進行中」になる
        $readingPlan->update([
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::InProgress,
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画を読了済みにする
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('complete', $readingPlan);

        $readingPlan->update([
            'completed_at' => Carbon::now(),
            'status' => ReadingPlanStatus::Completed,
        ]);

        return redirect()->route('reading-plans.index')->with('success', "「{$readingPlan->book->title}」を読了しました。");
    }

    /**
     * 読書計画を削除する
     *
     * 計画に紐づいて送信済みのリマインダー通知（ReadingPlanReminder）もあわせて削除する。
     * notifications はポリモーフィック関連のため外部キー制約による連鎖削除ができず、
     * ここで明示的に削除する。通知の削除と計画の削除は1つのトランザクションで行い、
     * どちらかが失敗した場合は両方とも元に戻す。
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        DB::transaction(function () use ($readingPlan) {
            // notifications.data は text カラムに保存されたJSONで、DB側のJSON演算子は
            // ドライバ（sqlite/mysql）によって扱いが異なるため、DatabaseNotification の
            // data キャスト（配列）を介してPHP側で plan_id を照合する。
            $readingPlan->user
                ->notifications()
                ->where('type', ReadingPlanReminder::class)
                ->get()
                ->filter(fn ($notification) => (int) ($notification->data['plan_id'] ?? 0) === (int) $readingPlan->id)
                ->each(fn ($notification) => $notification->delete());

            $readingPlan->delete();
        });

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました。');
    }
}
