<?php

namespace App\Http\Controllers\Review;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\ReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * レビューを投稿する
     */
    public function store(ReviewRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('create', [Review::class, $book]);

        $validated = $request->validated();

        $book->reviews()->create([
            'user_id' => Auth::id(),
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return back()->with('success', 'レビューを投稿しました。');
    }

    /**
     * レビューへのいいねを切り替える
     */
    public function like(Review $review): RedirectResponse
    {
        $user = Auth::user();

        if ($review->likedByUsers->contains($user->id)) {
            $review->likedByUsers()->detach($user->id);
        } else {
            $review->likedByUsers()->attach($user->id);
        }

        return back();
    }

    /**
     * レビュー編集画面を表示
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * レビューを更新する
     */
    public function update(ReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $validated = $request->validated();

        $review->update([
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return redirect()->route('books.show', $review->book)->with('success', 'レビューを更新しました。');
    }

    /**
     * レビューを削除する
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        return back()->with('success', 'レビューを削除しました。');
    }
}
