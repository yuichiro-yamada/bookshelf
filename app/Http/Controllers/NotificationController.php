<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * 同じ日時に作成された通知同士の表示順（通知 data の timing ごと）
     *
     * 日次バッチは同じ秒のうちに複数の通知を作成するため、作成日時だけでは
     * 並び順が決まらない。その場合はこの順（期日3日前 → 当日 → 3日後）で表示する。
     *
     * @var array<string, int>
     */
    private const TIMING_ORDER = [
        'three_days_before' => 1, // 期日3日前（予告）
        'on_due_date' => 2,       // 期日当日（最終警告）
        'three_days_after' => 3,  // 期日3日後（再エンゲージメント）
    ];

    /**
     * 通知一覧を表示する（ログインユーザー自身の通知のみ）
     *
     * 並び順は、第1に作成日時の新しい順、第2に通知の種類（TIMING_ORDER の順）。
     */
    public function index(): View
    {
        $notifications = auth()->user()->notifications()
            ->get()
            ->sortBy([
                fn ($a, $b) => $b->created_at <=> $a->created_at,
                fn ($a, $b) => $this->timingOrder($a) <=> $this->timingOrder($b),
            ])
            ->values();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 通知を既読にする
     *
     * ログインユーザー自身の通知のみ対象。他人の通知IDや存在しないIDの場合は404になる。
     *
     * @param  string  $id  通知ID（UUID）
     */
    public function markAsRead(string $id): RedirectResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // 何の通知を既読にしたか分かるよう、対象の書籍名をメッセージに含める
        $bookTitle = $notification->data['book_title'] ?? null;
        $message = $bookTitle
            ? "「{$bookTitle}」の通知を既読にしました。"
            : '通知を既読にしました。';

        return back()->with('success', $message);
    }

    /**
     * 通知の種類（data の timing）から表示順を返す（未知の種類は最後）
     */
    private function timingOrder(DatabaseNotification $notification): int
    {
        return self::TIMING_ORDER[$notification->data['timing'] ?? ''] ?? PHP_INT_MAX;
    }
}
