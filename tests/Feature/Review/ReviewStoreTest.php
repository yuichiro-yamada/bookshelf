<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * レビュー投稿機能のテスト（テストケース一覧「レビュー投稿」に対応）
 */
class ReviewStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_rating_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => '',
            'comment' => '感想',
        ]);

        $response->assertSessionHasErrors(['rating' => '評価を入力してください']);
    }

    public function test_comment_must_not_exceed_255_characters(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => str_repeat('あ', 256),
        ]);

        $response->assertSessionHasErrors(['comment' => 'コメントは255文字以内で入力してください']);
    }

    public function test_comment_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 4,
        ]);

        $response->assertSessionHasErrors(['comment' => 'コメントを入力してください']);
    }

    public function test_review_is_posted_with_valid_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => 'とても良い本でした',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'レビューを投稿しました。');
        $this->assertDatabaseHas('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'とても良い本でした',
        ]);
    }

    public function test_user_cannot_post_a_second_review_for_the_same_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        Review::factory()->for($book)->for($user)->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 3,
            'comment' => 'コメント',
        ]);

        $response->assertForbidden();
    }
}
