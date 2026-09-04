<?php

namespace App\Http\Controllers\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReadingPlan\ReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧を表示する（ログインユーザー自身の計画のみ）
     */
    public function index(Request $request): View
    {
        $currentStatus = $request->query('status');

        $readingPlans = ReadingPlan::with('book')
            ->where('user_id', Auth::id())
            ->orderBy('target_date')
            ->get();

        $statusFilter = ReadingPlanStatus::tryFrom((string) $currentStatus);

        if ($statusFilter !== null) {
            $readingPlans = $readingPlans
                ->filter(fn (ReadingPlan $plan) => $plan->status === $statusFilter)
                ->values();
        }

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画作成画面を表示する
     */
    public function create(): View
    {
        // すでに「進行中」の計画がある書籍は選択肢から除外する
        $activeBookIds = ReadingPlan::where('user_id', Auth::id())
            ->whereNull('completed_at')
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
        $validated = $request->validated();

        $book = Book::findOrFail($validated['book_id']);

        $this->authorize('create', [ReadingPlan::class, $book]);

        ReadingPlan::create([
            'book_id' => $book->id,
            'user_id' => Auth::id(),
            'target_date' => $validated['target_date'],
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
        $this->authorize('update', $readingPlan);

        $validated = $request->validated();

        $readingPlan->update([
            'target_date' => $validated['target_date'],
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画を読了済みにする
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->update([
            'completed_at' => Carbon::today(),
        ]);

        return redirect()->route('reading-plans.index')->with('success', "「{$readingPlan->book->title}」を読了しました。");
    }

    /**
     * 読書計画を削除する
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました。');
    }
}
