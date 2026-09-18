<?php

namespace Tests\Feature\BookCrud;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍削除機能のテスト（テストケース一覧「書籍削除」に対応）
 */
class BookDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_delete_book(): void
    {
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->delete(route('books.destroy', $book));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    public function test_other_user_cannot_delete_book(): void
    {
        $otherUser = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->actingAs($otherUser)->delete(route('books.destroy', $book));

        $response->assertForbidden();
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    public function test_delete_button_shows_confirmation_dialog(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($owner)->get(route('books.show', $book));

        $response->assertSee('本当に削除しますか？');
    }

    public function test_owner_can_delete_book(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($owner)->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));
        $response->assertSessionHas('success', '書籍を削除しました。');
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }
}
