<?php

namespace Tests\Feature\Genre;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ジャンル削除機能のテスト（テストケース一覧「ジャンル削除」に対応）
 */
class GenreDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_delete_genre(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }

    public function test_genre_linked_to_books_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();
        $book->genres()->attach($genre);

        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'このジャンルは書籍に紐づいているため削除できません。');
        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }

    public function test_genre_not_linked_to_any_book_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'ジャンルを削除しました。');
        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }
}
