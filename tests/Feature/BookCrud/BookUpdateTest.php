<?php

namespace Tests\Feature\BookCrud;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍編集機能のテスト（テストケース一覧「書籍編集」に対応）
 */
class BookUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_book_edit_page(): void
    {
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->get(route('books.edit', $book));

        $response->assertRedirect(route('login'));
    }

    public function test_other_user_cannot_edit_book(): void
    {
        $otherUser = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->actingAs($otherUser)->get(route('books.edit', $book));

        $response->assertForbidden();
    }

    public function test_edit_page_shows_current_book_information(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($owner)->create(['title' => '編集前タイトル']);
        $book->genres()->attach($genre);

        $response = $this->actingAs($owner)->get(route('books.edit', $book));

        $response->assertOk();
        $response->assertSee('編集前タイトル');
    }

    public function test_validation_errors_are_shown_same_as_store(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($owner)->put(route('books.update', $book), [
            'title' => '',
            'author' => '夏目漱石',
            'isbn' => '9784000000001',
            'published_date' => '2000-01-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['title' => '書籍タイトルを入力してください']);
    }

    public function test_unique_check_excludes_the_book_itself(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($owner)->create([
            'title' => '変わらないタイトル',
            'isbn' => '9784000000002',
        ]);
        $book->genres()->attach($genre);

        $response = $this->actingAs($owner)->put(route('books.update', $book), [
            'title' => '変わらないタイトル',
            'author' => '更新後の著者',
            'isbn' => '9784000000002',
            'published_date' => '2000-01-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionDoesntHaveErrors(['title', 'isbn']);
        $response->assertRedirect(route('books.show', $book));
    }

    public function test_book_is_updated_with_valid_data(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($owner)->create(['title' => '更新前']);
        $book->genres()->attach($genre);

        $response = $this->actingAs($owner)->put(route('books.update', $book), [
            'title' => '更新後',
            'author' => '更新後の著者',
            'isbn' => $book->isbn,
            'published_date' => '2001-01-01',
            'genres' => [$genre->id],
        ]);

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', '「更新後」の情報を更新しました。');
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '更新後']);
    }
}
