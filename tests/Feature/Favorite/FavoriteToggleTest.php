<?php

namespace Tests\Feature\Favorite;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * お気に入りの登録・解除機能のテスト（テストケース一覧 6-2「お気に入りの登録・解除」に対応）
 *
 * ハートアイコンの表示状態は、ボタンの title 属性
 * （登録済み:「お気に入りから削除」／未登録:「お気に入りに追加」）で判定する。
 */
class FavoriteToggleTest extends TestCase
{
    use RefreshDatabase;

    // ※ ビューは Auth::user() のリレーション（favoriteBooks / likedReviews）を参照するため、
    //   操作の前後で画面を確認するときは $user->fresh() でキャッシュされていないユーザーを使う。

    /**
     * 6-2-1 未登録の書籍をお気に入りに登録できる
     */
    public function test_user_can_add_a_book_to_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);

        // アイコンが登録済み（塗りつぶし）の表示に切り替わる
        $this->actingAs($user->fresh())->get(route('books.show', $book))
            ->assertSee('title="お気に入りから削除"', false)
            ->assertDontSee('title="お気に入りに追加"', false);
    }

    /**
     * 6-2-2 登録済みの書籍のお気に入りを解除できる
     */
    public function test_user_can_remove_a_book_from_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create(['title' => '解除される本']);
        $user->favoriteBooks()->attach($book);

        $response = $this->actingAs($user)
            ->from(route('favorites.index'))
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('favorites.index'));
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);

        // 詳細ページのアイコンが未登録の表示に切り替わる
        $this->actingAs($user->fresh())->get(route('books.show', $book))
            ->assertSee('title="お気に入りに追加"', false)
            ->assertDontSee('title="お気に入りから削除"', false);

        // お気に入り一覧からも消える
        $this->actingAs($user->fresh())->get(route('favorites.index'))
            ->assertDontSee('解除される本');
    }

    /**
     * 6-2-3 未ログインの場合、お気に入りボタンの代わりにログインへの案内が表示される
     */
    public function test_guest_sees_login_link_instead_of_favorite_button(): void
    {
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee(
            'href="'.route('login').'" class="text-gray-400 hover:text-red-500" title="お気に入りに追加するにはログインしてください"',
            false
        );
        $response->assertDontSee('action="'.route('favorites.toggle', $book).'"', false);

        // 直接リクエストを送ってもログインページにリダイレクトされる
        $this->post(route('favorites.toggle', $book))->assertRedirect(route('login'));
        $this->assertDatabaseCount('favorites', 0);
    }
}
