<?php

namespace Tests\Feature\Favorite;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * お気に入りの登録・解除のテスト（テストケース一覧「お気に入りの登録・解除」に対応）
 */
class FavoriteToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_a_book_to_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->actingAs($user)->post(route('favorites.toggle', $book));

        $response->assertRedirect();
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);
    }

    public function test_user_can_remove_a_book_from_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $user->favoriteBooks()->attach($book);

        $response = $this->actingAs($user)->post(route('favorites.toggle', $book));

        $response->assertRedirect();
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);
    }

    public function test_guest_cannot_toggle_favorite(): void
    {
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('login'));
    }
}
