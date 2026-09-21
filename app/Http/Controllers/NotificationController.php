<?php

namespace App\Http\Controllers;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->get();
        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(string $id)
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
