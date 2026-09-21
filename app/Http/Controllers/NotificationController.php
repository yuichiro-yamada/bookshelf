<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * 通知一覧を表示する（ログインユーザー自身の通知のみ、新しい順）
     */
    public function index(): View
    {
        $notifications = auth()->user()->notifications()->latest()->get();
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
}
