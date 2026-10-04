<?php

namespace Tests\Feature\Favorite;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * お気に入り一覧機能のテスト（テストケース一覧 6-1「お気に入り一覧表示」に対応）
 */
class FavoriteIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 6-1-1 お気に入りに登録した書籍が一覧表示される
     */
    public function test_favorited_books_are_listed(): void
    {
        $user = User::factory()->create();
        $favoriteBook = Book::factory()->for(User::factory())->create(['title' => 'お気に入りの本']);
        Book::factory()->for(User::factory())->create(['title' => 'お気に入りではない本']);
        $user->favoriteBooks()->attach($favoriteBook);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk();
        $response->assertSee('お気に入りの本');
        $response->assertDontSee('お気に入りではない本');
    }

    /**
     * 6-1-2 お気に入りが1件も登録されていない場合の表示を確認する
     */
    public function test_shows_message_and_link_when_no_favorites_registered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk();
        $response->assertSee('お気に入りに登録された書籍はありません。');
        $response->assertSee('書籍一覧を見る');
        $response->assertSee('href="'.route('books.index').'"', false);
    }

    /**
     * 6-1-3 未ログインの場合、お気に入り一覧ページにアクセスできない
     */
    public function test_guest_cannot_access_favorite_list(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 6-1-4 お気に入りに登録した日時の新しい順に表示される
     */
    public function test_favorites_are_listed_in_newest_favorited_order(): void
    {
        $user = User::factory()->create();
        // 書籍の登録順（古→新）とは逆の順番でお気に入り登録する
        $first = Book::factory()->create(['title' => '最初に登録された書籍', 'created_at' => now()->subDays(3)]);
        $second = Book::factory()->create(['title' => '2番目に登録された書籍', 'created_at' => now()->subDays(2)]);
        $third = Book::factory()->create(['title' => '3番目に登録された書籍', 'created_at' => now()->subDay()]);
        $user->favoriteBooks()->attach($third->id, ['created_at' => now()->subHours(3), 'updated_at' => now()->subHours(3)]);
        $user->favoriteBooks()->attach($first->id, ['created_at' => now()->subHours(2), 'updated_at' => now()->subHours(2)]);
        $user->favoriteBooks()->attach($second->id, ['created_at' => now()->subHour(), 'updated_at' => now()->subHour()]);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $expected = ['2番目に登録された書籍', '最初に登録された書籍', '3番目に登録された書籍'];

        $response->assertOk();
        $response->assertViewHas('books', fn ($books) => $books->pluck('title')->all() === $expected);
        $response->assertSeeInOrder($expected);
    }
}
