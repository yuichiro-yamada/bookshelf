<?php

namespace Tests\Feature\Favorite;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * お気に入り一覧表示のテスト（テストケース一覧「お気に入り一覧表示」に対応）
 */
class FavoriteIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorited_books_are_listed(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create(['title' => 'お気に入りの本']);
        $user->favoriteBooks()->attach($book);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk();
        $response->assertSee('お気に入りの本');
    }

    public function test_shows_message_when_no_favorites_registered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk();
        $response->assertSee('お気に入りに登録された書籍はありません。');
    }

    public function test_guest_cannot_access_favorite_list(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertRedirect(route('login'));
    }
}
