<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * レビュー編集機能のテスト（テストケース一覧 4-3「レビュー編集」に対応）
 */
class ReviewUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 4-3-1 自分が投稿したレビューにのみ「編集」リンクが表示される
     */
    public function test_only_owner_sees_edit_link(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $ownReview = Review::factory()->for($book)->for($owner)->create();
        $othersReview = Review::factory()->for($book)->for($otherUser)->create();

        $response = $this->actingAs($owner)->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee(route('reviews.edit', $ownReview));
        $response->assertDontSee(route('reviews.edit', $othersReview));
    }

    /**
     * 4-3-2 編集画面を開くと現在の評価・コメントが初期表示される
     */
    public function test_edit_page_shows_current_rating_and_comment(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create([
            'rating' => 4,
            'comment' => '編集前コメント',
        ]);

        $response = $this->actingAs($owner)->get(route('reviews.edit', $review));

        $response->assertOk();
        $response->assertSee('編集前コメント');

        // 現在の評価（★4）のラジオボタンだけが checked になっている
        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/value="4"\s+class="sr-only peer"\s+checked/', $content);
        foreach ([1, 2, 3, 5] as $rating) {
            $this->assertDoesNotMatchRegularExpression('/value="'.$rating.'"\s+class="sr-only peer"\s+checked/', $content);
        }
    }

    /**
     * 4-3-3 評価が選択されていない場合、バリデーションメッセージが表示される
     */
    public function test_rating_is_required(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create();

        $response = $this->actingAs($owner)->put(route('reviews.update', $review), [
            'rating' => '',
            'comment' => 'コメント',
        ]);

        $response->assertSessionHasErrors(['rating' => '評価を入力してください']);
    }

    /**
     * 4-3-4 コメントが256文字以上の場合、バリデーションメッセージが表示される
     */
    public function test_comment_must_not_exceed_255_characters(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create();

        $response = $this->actingAs($owner)->put(route('reviews.update', $review), [
            'rating' => 4,
            'comment' => str_repeat('あ', 256),
        ]);

        $response->assertSessionHasErrors(['comment' => 'コメントは255文字以内で入力してください']);
    }

    /**
     * 4-3-5 他人が投稿したレビューは更新できない
     */
    public function test_other_user_cannot_update_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create(['rating' => 5, 'comment' => '元のコメント']);

        $response = $this->actingAs($otherUser)->put(route('reviews.update', $review), [
            'rating' => 1,
            'comment' => '書き換えたコメント',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 5, 'comment' => '元のコメント']);
    }

    /**
     * 4-3-6 全ての項目が正しく入力されている場合、レビューが更新される
     */
    public function test_review_is_updated_with_valid_data(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create();

        $response = $this->actingAs($owner)->put(route('reviews.update', $review), [
            'rating' => 2,
            'comment' => '更新後のコメント',
        ]);

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', 'レビューを更新しました。');
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 2, 'comment' => '更新後のコメント']);

        $this->get(route('books.show', $book))->assertSee('レビューを更新しました。');
    }

    /**
     * 4-3-7 コメントが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_comment_is_required(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create(['comment' => '編集前コメント']);

        $response = $this->actingAs($owner)->put(route('reviews.update', $review), [
            'rating' => 4,
            'comment' => '',
        ]);

        $response->assertSessionHasErrors(['comment' => 'コメントを入力してください']);
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'comment' => '編集前コメント']);
    }
}
