<?php

namespace Tests\Feature\Genre;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ジャンル詳細機能のテスト（テストケース一覧 5-3「ジャンル詳細」に対応）
 */
class GenreShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 5-3-1 ジャンルに紐づく書籍が一覧表示される
     */
    public function test_books_linked_to_genre_are_listed(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $otherGenre = Genre::factory()->create();
        $linkedBook = Book::factory()->for($user)->create(['title' => 'ジャンル紐づき書籍']);
        $linkedBook->genres()->attach($genre);
        $otherBook = Book::factory()->for($user)->create(['title' => '別ジャンルの書籍']);
        $otherBook->genres()->attach($otherGenre);

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertSee('ジャンル紐づき書籍');
        $response->assertDontSee('別ジャンルの書籍');
    }

    /**
     * 5-3-2 ジャンルに紐づく書籍が1件もない場合の表示を確認する
     */
    public function test_shows_message_when_no_books_are_linked(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertSee('このジャンルの書籍はまだ登録されていません。');
    }
}
