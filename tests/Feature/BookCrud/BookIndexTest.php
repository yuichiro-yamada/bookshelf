<?php

namespace Tests\Feature\BookCrud;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍一覧機能のテスト（テストケース一覧 3-1「書籍一覧」に対応）
 */
class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 3-1-1 書籍一覧ページを表示する
     */
    public function test_book_list_is_displayed_in_newest_order(): void
    {
        $genre = Genre::factory()->create(['name' => 'ミステリー']);

        $oldBook = Book::factory()->for(User::factory())->create([
            'title' => '古い書籍',
            'created_at' => now()->subDay(),
        ]);
        $newBook = Book::factory()->for(User::factory())->create([
            'title' => '新しい書籍',
            'author' => '夏目漱石',
            'image_url' => 'https://example.com/new-cover.jpg',
            'created_at' => now(),
        ]);
        $newBook->genres()->attach($genre);
        Review::factory()->for($newBook)->for(User::factory())->create(['rating' => 4]);

        $response = $this->get(route('books.index'));

        $response->assertOk();
        // 新着順（登録日時の新しい順）に並んでいる
        $response->assertSeeInOrder(['新しい書籍', '古い書籍']);
        // 書籍画像・タイトル・著者名・ジャンル・平均評価が表示される
        $response->assertSee('https://example.com/new-cover.jpg');
        $response->assertSee('夏目漱石');
        $response->assertSee('ミステリー');
        $response->assertSee('(4.0)');
    }

    /**
     * 3-1-2 登録件数が1ページの表示件数（10件）を超える場合、ページネーションが表示される
     */
    public function test_book_list_is_paginated_by_ten_items(): void
    {
        Book::factory()->for(User::factory())->count(11)->create();

        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10 && $books->hasMorePages();
        });

        $secondPage = $this->get(route('books.index', ['page' => 2]));

        $secondPage->assertOk();
        $secondPage->assertViewHas('books', fn ($books) => $books->count() === 1);
    }

    /**
     * 3-1-3 書籍が1件も登録されていない場合の表示を確認する
     */
    public function test_shows_message_when_no_books_are_registered(): void
    {
        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertSee('書籍が登録されていません。');
    }

    /**
     * 3-1-4 未ログインの状態でも書籍一覧ページを閲覧できる
     */
    public function test_guest_can_view_book_list(): void
    {
        Book::factory()->for(User::factory())->create(['title' => 'ゲストにも見える書籍']);

        $response = $this->get(route('books.index'));

        $response->assertOk();
        $this->assertGuest();
        $response->assertSee('ゲストにも見える書籍');
    }
}
