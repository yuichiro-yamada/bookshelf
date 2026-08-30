<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * レビュー表示のテスト（テストケース一覧「レビュー表示」に対応）
 */
class ReviewDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviews_are_listed_on_book_detail_page(): void
    {
        $book = Book::factory()->for(User::factory())->create();
        $reviewer = User::factory()->create(['name' => 'レビュー太郎']);
        Review::factory()->for($book)->for($reviewer)->create(['comment' => 'とても良かったです']);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('レビュー太郎');
        $response->assertSee('とても良かったです');
    }

    public function test_shows_message_when_no_reviews_exist(): void
    {
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('まだレビューはありません。');
    }

    public function test_guest_sees_login_prompt_instead_of_review_form(): void
    {
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->get(route('books.show', $book));

        $response->assertSee('レビューを投稿するには');
    }

    public function test_shows_already_reviewed_message_when_user_already_posted(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        Review::factory()->for($book)->for($user)->create();

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertSee('レビューは1書籍に1つまでです');
    }
}
