<?php

namespace Tests\Feature\Genre;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ジャンル一覧機能のテスト（テストケース一覧「ジャンル一覧」に対応）
 */
class GenreIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_genre_list_shows_name_and_book_count(): void
    {
        $genre = Genre::factory()->create(['name' => 'ミステリー']);
        $book = Book::factory()->for(User::factory())->create();
        $book->genres()->attach($genre);

        $response = $this->get(route('genres.index'));

        $response->assertOk();
        $response->assertSee('ミステリー');
        $response->assertSee('1冊');
    }

    public function test_shows_message_when_no_genres_are_registered(): void
    {
        $response = $this->get(route('genres.index'));

        $response->assertOk();
        $response->assertSee('ジャンルが登録されていません。');
    }

    public function test_guest_can_view_genre_list(): void
    {
        $response = $this->get(route('genres.index'));

        $response->assertOk();
    }
}
