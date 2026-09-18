<?php

namespace Tests\Feature\BookCrud;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍一覧機能のテスト（テストケース一覧「書籍一覧」に対応）
 */
class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_list_is_displayed(): void
    {
        $book = Book::factory()->for(User::factory())->create(['title' => '吾輩は猫である']);

        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertSee('吾輩は猫である');
    }

    public function test_book_list_is_paginated_when_exceeding_nine_items(): void
    {
        Book::factory()->for(User::factory())->count(10)->create();

        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 9 && $books->hasMorePages();
        });
    }

    public function test_shows_message_when_no_books_are_registered(): void
    {
        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertSee('書籍が登録されていません。');
    }

    public function test_guest_can_view_book_list(): void
    {
        $response = $this->get(route('books.index'));

        $response->assertOk();
    }
}
