<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * レビュー削除機能のテスト（テストケース一覧 4-4「レビュー削除」に対応）
 */
class ReviewDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 4-4-1 自分が投稿したレビューにのみ「削除」ボタンが表示される
     */
    public function test_only_owner_sees_delete_button(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $ownReview = Review::factory()->for($book)->for($owner)->create();
        $othersReview = Review::factory()->for($book)->for($otherUser)->create();

        $response = $this->actingAs($owner)->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('action="'.route('reviews.destroy', $ownReview).'"', false);
        $response->assertDontSee('action="'.route('reviews.destroy', $othersReview).'"', false);
    }

    /**
     * 4-4-2 他人が投稿したレビューは削除できない
     */
    public function test_other_user_cannot_delete_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create();

        $response = $this->actingAs($otherUser)->delete(route('reviews.destroy', $review));

        $response->assertForbidden();
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    /**
     * 4-4-3 削除ボタンを押すと確認ダイアログが表示される
     *
     * ダイアログ自体はブラウザのJavaScript（confirm）で表示されるため、
     * レビューの削除フォームに確認ダイアログの処理が設定されていることを確認する。
     */
    public function test_delete_button_shows_confirmation_dialog(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create();

        $response = $this->actingAs($owner)->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee(
            'action="'.route('reviews.destroy', $review).'" method="POST" onsubmit="return confirm(\'本当に削除しますか？\')"',
            false
        );
    }

    /**
     * 4-4-4 確認ダイアログでOKを押すとレビューが削除される
     */
    public function test_owner_can_delete_review(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create(['comment' => '削除されるレビュー']);

        $response = $this->actingAs($owner)
            ->from(route('books.show', $book))
            ->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', 'レビューを削除しました。');
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);

        // メッセージが表示され、一覧から消えている
        $this->get(route('books.show', $book))
            ->assertSee('レビューを削除しました。')
            ->assertDontSee('削除されるレビュー')
            ->assertSee('まだレビューはありません。');
    }
}
