<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * レビュー表示機能のテスト（テストケース一覧 4-1「レビュー表示」に対応）
 */
class ReviewDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 4-1-1 書籍に投稿されたレビューが一覧表示される
     */
    public function test_reviews_are_listed_on_book_detail_page_in_newest_order(): void
    {
        $book = Book::factory()->for(User::factory())->create();
        Review::factory()->for($book)->for(User::factory()->create(['name' => '古い投稿者']))->create([
            'rating' => 2,
            'comment' => '古いレビューです',
            'created_at' => '2026-01-01 10:00:00',
        ]);
        Review::factory()->for($book)->for(User::factory()->create(['name' => '新しい投稿者']))->create([
            'rating' => 4,
            'comment' => '新しいレビューです',
            'created_at' => '2026-02-01 10:00:00',
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        // 投稿者名・評価（星）・コメント・投稿日時が、新しい順に表示される
        $response->assertSeeInOrder([
            '新しい投稿者', '★★★★☆', '2026/02/01', '新しいレビューです',
            '古い投稿者', '★★☆☆☆', '2026/01/01', '古いレビューです',
        ]);
    }

    /**
     * 4-1-2 レビューが1件も投稿されていない場合の表示を確認する
     */
    public function test_shows_message_when_no_reviews_exist(): void
    {
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('まだレビューはありません。');
    }

    /**
     * 4-1-3 未ログインの場合、レビュー投稿フォームの代わりにログインを促す文言が表示される
     */
    public function test_guest_sees_login_prompt_instead_of_review_form(): void
    {
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSeeText('レビューを投稿するにはログインしてください。');
        $response->assertDontSee(route('reviews.store', $book));
    }

    /**
     * 4-1-4 既にその書籍にレビューを投稿済みの場合、投稿フォームの代わりに案内文が表示される
     */
    public function test_shows_already_reviewed_message_when_user_already_posted(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        Review::factory()->for($book)->for($user)->create();

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('この書籍に対するあなたのレビューはすでに投稿されています。レビューは1書籍に1つまでです。');
        $response->assertDontSee(route('reviews.store', $book));
    }
}
