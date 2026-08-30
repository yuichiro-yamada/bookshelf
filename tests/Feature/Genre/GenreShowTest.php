<?php

namespace Tests\Feature\Genre;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ジャンル詳細機能のテスト（テストケース一覧「ジャンル詳細」に対応）
 */
class GenreShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_linked_to_genre_are_listed(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->for(User::factory())->create(['title' => 'ジャンル紐づき書籍']);
        $book->genres()->attach($genre);

        $response = $this->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertSee('ジャンル紐づき書籍');
    }

    public function test_shows_message_when_no_books_are_linked(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertSee('このジャンルの書籍はまだ登録されていません。');
    }
}
