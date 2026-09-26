<?php

namespace Tests\Feature\Ranking;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ランキング機能のテスト（テストケース一覧 8-1「ランキングページ表示」に対応）
 */
class RankingIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 指定した評価のレビューを、それぞれ別のユーザーで投稿する
     *
     * reviews テーブルは (user_id, book_id) がユニークなため、
     * 1冊に複数のレビューを付ける場合はユーザーを分ける必要がある。
     *
     * @param  array<int, int>  $ratings
     */
    private function addReviews(Book $book, array $ratings): void
    {
        foreach ($ratings as $rating) {
            Review::factory()->for($book)->for(User::factory())->create(['rating' => $rating]);
        }
    }

    /**
     * 8-1-1 レビューの平均評価が高い順に書籍が表示される
     */
    public function test_books_are_ordered_by_average_rating_and_limited_to_top_ten(): void
    {
        // 平均評価がすべて異なる11冊を用意する（上位10件のみ表示される）
        // n冊目には「★5を1件＋★1を(n-1)件」のレビューを付けるため、平均評価は
        // 5.0, 3.0, 2.33…, 2.0, 1.8 … と、nが大きいほど低くなる
        $expectedTitles = [];
        for ($n = 1; $n <= 11; $n++) {
            $book = Book::factory()->for(User::factory())->create(['title' => sprintf('ランキング書籍%02d', $n)]);
            $this->addReviews($book, array_merge([5], array_fill(0, $n - 1, 1)));
            $expectedTitles[] = $book->title;
        }

        // レビューが投稿されていない書籍はランキングに含まれない
        Book::factory()->for(User::factory())->create(['title' => 'レビューなしの書籍']);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSeeInOrder(array_slice($expectedTitles, 0, 10));
        $response->assertViewHas('rankedBooks', fn ($books) => $books->count() === 10);
        $response->assertDontSee('ランキング書籍11');
        $response->assertDontSee('レビューなしの書籍');
    }

    /**
     * 8-1-2 平均評価が同じ場合、レビュー件数が多い順に表示される
     */
    public function test_books_with_same_average_rating_are_ordered_by_review_count_then_published_date(): void
    {
        $fewerReviewsBook = Book::factory()->for(User::factory())->create(['title' => 'レビュー数が少ない本']);
        $this->addReviews($fewerReviewsBook, [4]);

        $moreReviewsBook = Book::factory()->for(User::factory())->create(['title' => 'レビュー数が多い本']);
        $this->addReviews($moreReviewsBook, [4, 4]);

        // 平均評価・レビュー件数も同じ場合は、出版日が新しい順
        $olderBook = Book::factory()->for(User::factory())->create(['title' => '出版日が古い本', 'published_date' => '2000-01-01']);
        $this->addReviews($olderBook, [3]);
        $newerBook = Book::factory()->for(User::factory())->create(['title' => '出版日が新しい本', 'published_date' => '2020-01-01']);
        $this->addReviews($newerBook, [3]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['レビュー数が多い本', 'レビュー数が少ない本', '出版日が新しい本', '出版日が古い本']);
    }

    /**
     * 8-1-3 レビューが投稿された書籍が1件もない場合の表示を確認する
     */
    public function test_shows_message_when_no_book_has_reviews(): void
    {
        Book::factory()->for(User::factory())->create();

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSee('まだレビューが投稿された書籍がありません。');
    }

    /**
     * 8-1-4 未ログインの状態でもランキングページを閲覧できる
     */
    public function test_guest_can_view_ranking_page(): void
    {
        $book = Book::factory()->for(User::factory())->create(['title' => 'ゲストにも見えるランキング書籍']);
        $this->addReviews($book, [5]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $this->assertGuest();
        $response->assertSee('ゲストにも見えるランキング書籍');
    }
}
