<?php

namespace Tests\Feature\PublicApi;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍一覧取得APIのテスト（テストケース一覧 10-2「書籍一覧取得API(GET /api/v1/books)」に対応）
 */
class BookApiIndexTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/v1/books';

    /**
     * 10-2-1 認証なしでもアクセスでき、書籍一覧が返る
     */
    public function test_guest_can_get_book_list(): void
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson(self::URI);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'author', 'isbn', 'published_date', 'description', 'image_url', 'user_id', 'genres', 'average_rating', 'review_count'],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(3, 'data');
    }

    /**
     * 10-2-2 各書籍にジャンル・平均評価・レビュー件数が含まれる
     */
    public function test_each_book_contains_genres_average_rating_and_review_count(): void
    {
        $genre = Genre::factory()->create(['name' => 'ミステリー']);
        $book = Book::factory()->create();
        $book->genres()->attach($genre);
        Review::factory()->for($book)->create(['rating' => 4]);
        Review::factory()->for($book)->create(['rating' => 5]);

        $response = $this->getJson(self::URI);

        $response->assertOk();
        $response->assertJsonPath('data.0.genres', [['id' => $genre->id, 'name' => 'ミステリー']]);
        $response->assertJsonPath('data.0.average_rating', 4.5);
        $response->assertJsonPath('data.0.review_count', 2);
        $this->assertArrayNotHasKey('reviews', $response->json('data.0'));
    }

    /**
     * 10-2-3 レビューが無い書籍はaverage_ratingがnull、review_countが0になる
     */
    public function test_book_without_reviews_has_null_average_and_zero_count(): void
    {
        Book::factory()->create();

        $response = $this->getJson(self::URI);

        $response->assertOk();
        $response->assertJsonPath('data.0.average_rating', null);
        $response->assertJsonPath('data.0.review_count', 0);
    }

    /**
     * 10-2-4 新しい順(登録日時の降順、同一なら idの降順)で返る
     */
    public function test_books_are_returned_in_newest_order(): void
    {
        $old = Book::factory()->create(['created_at' => now()->subDays(2)]);
        $new = Book::factory()->create(['created_at' => now()]);
        $middle = Book::factory()->create(['created_at' => now()->subDay()]);
        // 登録日時が同じ場合は id の降順
        $sameTimeFirst = Book::factory()->create(['created_at' => now()->subDays(3)]);
        $sameTimeSecond = Book::factory()->create(['created_at' => $sameTimeFirst->created_at]);

        $response = $this->getJson(self::URI);

        $response->assertOk();
        $this->assertSame(
            [$new->id, $middle->id, $old->id, $sameTimeSecond->id, $sameTimeFirst->id],
            array_column($response->json('data'), 'id')
        );
    }

    /**
     * 10-2-5 keywordでタイトル・著者名の部分一致検索ができる
     */
    public function test_keyword_searches_title_and_author(): void
    {
        $titleMatch = Book::factory()->create(['title' => '猫と暮らす本', 'author' => '山田太郎']);
        $authorMatch = Book::factory()->create(['title' => '犬の本', 'author' => '猫田花子']);
        Book::factory()->create(['title' => '鳥の本', 'author' => '鈴木一郎']);

        $response = $this->getJson(self::URI.'?'.http_build_query(['keyword' => '猫']));

        $response->assertOk();
        $this->assertEqualsCanonicalizing(
            [$titleMatch->id, $authorMatch->id],
            array_column($response->json('data'), 'id')
        );
    }

    /**
     * 10-2-6 genreでジャンルIDによる絞り込みができる
     */
    public function test_genre_filters_books_by_genre_id(): void
    {
        $mystery = Genre::factory()->create();
        $sf = Genre::factory()->create();
        $mysteryBook = Book::factory()->create();
        $mysteryBook->genres()->attach($mystery);
        $sfBook = Book::factory()->create();
        $sfBook->genres()->attach($sf);

        $response = $this->getJson(self::URI.'?genre='.$mystery->id);

        $response->assertOk();
        $this->assertSame([$mysteryBook->id], array_column($response->json('data'), 'id'));
    }

    /**
     * 10-2-7 per_page・pageでページネーションできる(指定なしの場合は1ページ10件)
     */
    public function test_pagination_with_per_page_and_page(): void
    {
        Book::factory()->count(11)->create();

        // パラメータなし：1ページ10件
        $default = $this->getJson(self::URI);
        $default->assertOk();
        $default->assertJsonCount(10, 'data');
        $default->assertJsonPath('meta.per_page', 10);
        $default->assertJsonPath('meta.current_page', 1);
        $default->assertJsonPath('meta.last_page', 2);
        $default->assertJsonPath('meta.total', 11);

        // per_page・page を指定
        $paged = $this->getJson(self::URI.'?per_page=5&page=3');
        $paged->assertOk();
        $paged->assertJsonCount(1, 'data');
        $paged->assertJsonPath('meta.per_page', 5);
        $paged->assertJsonPath('meta.current_page', 3);
        $paged->assertJsonPath('meta.last_page', 3);
        $this->assertStringContainsString('page=2', $paged->json('links.prev'));
        $this->assertNull($paged->json('links.next'));
    }

    /**
     * 10-2-8 不正なクエリパラメータを指定すると422が返る
     */
    public function test_invalid_query_parameters_return_422(): void
    {
        $this->getJson(self::URI.'?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page' => '取得件数は100以下の値で指定してください']);

        $this->getJson(self::URI.'?genre=999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['genre' => '指定されたジャンルは存在しません']);

        $this->getJson(self::URI.'?page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page' => 'ページ番号は1以上の値で指定してください']);
    }
}
