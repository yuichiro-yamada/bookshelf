<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * レビュー投稿機能のテスト（テストケース一覧 4-2「レビュー投稿」に対応）
 */
class ReviewStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 4-2-1 評価が選択されていない場合、バリデーションメッセージが表示される
     */
    public function test_rating_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => '',
            'comment' => '感想',
        ]);

        $response->assertSessionHasErrors(['rating' => '評価を入力してください']);
        $this->assertDatabaseCount('reviews', 0);
    }

    /**
     * 4-2-2 コメントが256文字以上の場合、バリデーションメッセージが表示される
     */
    public function test_comment_must_not_exceed_255_characters(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => str_repeat('あ', 256),
        ]);

        $response->assertSessionHasErrors(['comment' => 'コメントは255文字以内で入力してください']);
        $this->assertDatabaseCount('reviews', 0);
    }

    /**
     * 4-2-3 コメントが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_comment_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 4,
            'comment' => '',
        ]);

        $response->assertSessionHasErrors(['comment' => 'コメントを入力してください']);
        $this->assertDatabaseCount('reviews', 0);
    }

    /**
     * 4-2-4 全ての項目が正しく入力されている場合、レビューが投稿される
     */
    public function test_review_is_posted_with_valid_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.store', $book), [
                'rating' => 5,
                'comment' => 'とても良い本でした',
            ]);

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', 'レビューを投稿しました。');
        $this->assertDatabaseHas('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'とても良い本でした',
        ]);

        // 書籍詳細画面にメッセージが表示され、レビュー一覧に反映される
        $this->get(route('books.show', $book))
            ->assertSee('レビューを投稿しました。')
            ->assertSee('とても良い本でした');
    }

    /**
     * 4-2-5 既にレビューを投稿済みの書籍に対して、2件目のレビューを投稿しようとする
     */
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
        $this->assertSame(1, Review::where('book_id', $book->id)->where('user_id', $user->id)->count());
    }
}
