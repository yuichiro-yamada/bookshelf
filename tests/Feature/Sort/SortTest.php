<?php

namespace Tests\Feature\Sort;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍一覧の並び替えのテスト（テストケース一覧 12-1「書籍一覧の並び替え」に対応）
 *
 * 並び順は、ビューに渡された $books のタイトルの並びで確認する。
 */
class SortTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍一覧を取得し、表示順のタイトル一覧を返す
     *
     * @return array<int, string>
     */
    private function titlesInOrder(array $query = []): array
    {
        $response = $this->get(route('books.index', $query));
        $response->assertOk();

        return $response->viewData('books')->pluck('title')->all();
    }

    /**
     * 登録日時の異なる3冊を用意する
     */
    private function createBooksWithDifferentDates(): void
    {
        Book::factory()->create(['title' => 'B 中間の書籍', 'created_at' => now()->subDays(1)]);
        Book::factory()->create(['title' => 'C 最も古い書籍', 'created_at' => now()->subDays(2)]);
        Book::factory()->create(['title' => 'A 最も新しい書籍', 'created_at' => now()]);
    }

    /**
     * 12-1-1 並び替え未指定時は新着順(登録が新しい順)で表示される
     */
    public function test_default_order_is_newest_first(): void
    {
        $this->createBooksWithDifferentDates();

        $this->assertSame(
            ['A 最も新しい書籍', 'B 中間の書籍', 'C 最も古い書籍'],
            $this->titlesInOrder()
        );
    }

    /**
     * 12-1-2 sort=oldestを指定すると登録が古い順に表示される
     */
    public function test_oldest_order(): void
    {
        $this->createBooksWithDifferentDates();

        $this->assertSame(
            ['C 最も古い書籍', 'B 中間の書籍', 'A 最も新しい書籍'],
            $this->titlesInOrder(['sort' => 'oldest'])
        );
    }

    /**
     * 12-1-3 sort=titleを指定するとタイトルの昇順で表示される
     */
    public function test_title_order(): void
    {
        $this->createBooksWithDifferentDates();

        $this->assertSame(
            ['A 最も新しい書籍', 'B 中間の書籍', 'C 最も古い書籍'],
            $this->titlesInOrder(['sort' => 'title'])
        );

        // 登録順とタイトル順が異なるデータでも、タイトルの昇順になる
        Book::factory()->create(['title' => 'AA 追加の書籍', 'created_at' => now()->subDays(5)]);

        $this->assertSame(
            ['A 最も新しい書籍', 'AA 追加の書籍', 'B 中間の書籍', 'C 最も古い書籍'],
            $this->titlesInOrder(['sort' => 'title'])
        );
    }

    /**
     * 12-1-4 sort=ratingを指定すると平均評価が高い順に表示される
     */
    public function test_rating_order(): void
    {
        $low = Book::factory()->create(['title' => '低評価の書籍', 'created_at' => now()]);
        $high = Book::factory()->create(['title' => '高評価の書籍', 'created_at' => now()->subDays(2)]);
        $middle = Book::factory()->create(['title' => '中評価の書籍', 'created_at' => now()->subDay()]);
        Review::factory()->for($low)->create(['rating' => 1]);
        Review::factory()->for($high)->create(['rating' => 5]);
        Review::factory()->for($middle)->create(['rating' => 3]);

        $this->assertSame(
            ['高評価の書籍', '中評価の書籍', '低評価の書籍'],
            $this->titlesInOrder(['sort' => 'rating'])
        );
    }

    /**
     * 12-1-5 sort=rating指定時、平均評価が同じ書籍は新着順で表示される
     */
    public function test_rating_ties_are_ordered_by_newest(): void
    {
        $older = Book::factory()->create(['title' => '同評価・古い書籍', 'created_at' => now()->subDays(2)]);
        $newer = Book::factory()->create(['title' => '同評価・新しい書籍', 'created_at' => now()]);
        $top = Book::factory()->create(['title' => '最高評価の書籍', 'created_at' => now()->subDays(3)]);
        Review::factory()->for($older)->create(['rating' => 4]);
        Review::factory()->for($newer)->create(['rating' => 4]);
        Review::factory()->for($top)->create(['rating' => 5]);

        $this->assertSame(
            ['最高評価の書籍', '同評価・新しい書籍', '同評価・古い書籍'],
            $this->titlesInOrder(['sort' => 'rating'])
        );
    }
}
