<?php

namespace Tests\Feature\Ranking;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ランキングページ表示のテスト（テストケース一覧「ランキングページ表示」に対応）
 */
class RankingIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_are_ordered_by_average_rating_descending(): void
    {
        $lowRatedBook = Book::factory()->for(User::factory())->create(['title' => '低評価の本']);
        Review::factory()->for($lowRatedBook)->for(User::factory())->create(['rating' => 2]);

        $highRatedBook = Book::factory()->for(User::factory())->create(['title' => '高評価の本']);
        Review::factory()->for($highRatedBook)->for(User::factory())->create(['rating' => 5]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertTrue(strpos($content, '高評価の本') < strpos($content, '低評価の本'));
    }

    public function test_books_with_same_average_rating_are_ordered_by_review_count(): void
    {
        $fewerReviewsBook = Book::factory()->for(User::factory())->create(['title' => 'レビュー数が少ない本']);
        Review::factory()->for($fewerReviewsBook)->for(User::factory())->create(['rating' => 4]);

        $moreReviewsBook = Book::factory()->for(User::factory())->create(['title' => 'レビュー数が多い本']);
        // count()と for(User::factory()) を併用すると同一ユーザーが使い回されてしまう（reviewsのuser_id+book_idユニーク制約に抵触するため）
        // ユーザーごとに個別にレビューを作成する
        Review::factory()->for($moreReviewsBook)->for(User::factory())->create(['rating' => 4]);
        Review::factory()->for($moreReviewsBook)->for(User::factory())->create(['rating' => 4]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertTrue(strpos($content, 'レビュー数が多い本') < strpos($content, 'レビュー数が少ない本'));
    }

    public function test_shows_message_when_no_book_has_reviews(): void
    {
        Book::factory()->for(User::factory())->create();

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSee('まだレビューが投稿された書籍がありません。');
    }

    public function test_guest_can_view_ranking_page(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertOk();
    }
}
