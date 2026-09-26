<?php

namespace Tests\Feature\Search;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍のキーワード検索・ジャンル絞り込みのテスト
 * （テストケース一覧 11-1「書籍のキーワード検索・ジャンル絞り込み」に対応）
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 11-1-1 タイトルにキーワードを含む書籍が検索結果に表示される
     */
    public function test_book_with_keyword_in_title_is_found(): void
    {
        Book::factory()->create(['title' => '猫と暮らす日々', 'author' => '山田太郎']);

        $response = $this->get(route('books.index', ['keyword' => '猫']));

        $response->assertOk();
        $response->assertSee('猫と暮らす日々');
    }

    /**
     * 11-1-2 著者名にキーワードを含む書籍が検索結果に表示される
     */
    public function test_book_with_keyword_in_author_is_found(): void
    {
        Book::factory()->create(['title' => '犬の本', 'author' => '猫田花子']);

        $response = $this->get(route('books.index', ['keyword' => '猫']));

        $response->assertOk();
        $response->assertSee('犬の本');
    }

    /**
     * 11-1-3 キーワードに一致しない書籍は検索結果に表示されない
     */
    public function test_book_without_keyword_is_not_found(): void
    {
        Book::factory()->create(['title' => '猫と暮らす日々', 'author' => '山田太郎']);
        Book::factory()->create(['title' => '鳥の図鑑', 'author' => '鈴木一郎']);

        $response = $this->get(route('books.index', ['keyword' => '猫']));

        $response->assertOk();
        $response->assertSee('猫と暮らす日々');
        $response->assertDontSee('鳥の図鑑');
    }

    /**
     * 11-1-4 キーワード未入力の場合は全件が表示される
     */
    public function test_all_books_are_shown_without_keyword(): void
    {
        Book::factory()->create(['title' => '猫と暮らす日々']);
        Book::factory()->create(['title' => '鳥の図鑑']);

        $response = $this->get(route('books.index', ['keyword' => '']));

        $response->assertOk();
        $response->assertSee('猫と暮らす日々');
        $response->assertSee('鳥の図鑑');
        $response->assertViewHas('books', fn ($books) => $books->total() === 2);
    }

    /**
     * 11-1-5 ジャンルで絞り込むと、そのジャンルに紐づく書籍のみ表示される
     */
    public function test_books_are_filtered_by_genre(): void
    {
        $novel = Genre::factory()->create();
        $comic = Genre::factory()->create();
        Book::factory()->create(['title' => '小説の書籍'])->genres()->attach($novel);
        Book::factory()->create(['title' => '漫画の書籍'])->genres()->attach($comic);

        $response = $this->get(route('books.index', ['genre' => $novel->id]));

        $response->assertOk();
        $response->assertSee('小説の書籍');
        $response->assertDontSee('漫画の書籍');
    }

    /**
     * 11-1-6 キーワード検索とジャンル絞り込みを組み合わせて使用できる
     */
    public function test_keyword_and_genre_can_be_combined(): void
    {
        $novel = Genre::factory()->create();
        $comic = Genre::factory()->create();
        Book::factory()->create(['title' => '猫の小説'])->genres()->attach($novel);
        Book::factory()->create(['title' => '猫の漫画'])->genres()->attach($comic);
        Book::factory()->create(['title' => '犬の小説'])->genres()->attach($novel);

        $response = $this->get(route('books.index', ['keyword' => '猫', 'genre' => $novel->id]));

        $response->assertOk();
        $response->assertSee('猫の小説');
        $response->assertDontSee('猫の漫画');
        $response->assertDontSee('犬の小説');
    }

    /**
     * 11-1-7 検索結果が0件の場合の表示を確認する
     */
    public function test_shows_message_when_no_books_match(): void
    {
        Book::factory()->create(['title' => '猫と暮らす日々', 'author' => '山田太郎']);

        $response = $this->get(route('books.index', ['keyword' => '該当なしキーワード']));

        $response->assertOk();
        $response->assertSee('条件に一致する書籍が見つかりませんでした。');
        $response->assertDontSee('猫と暮らす日々');
    }
}
