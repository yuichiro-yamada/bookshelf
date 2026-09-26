<?php

namespace Tests\Feature\Like;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * レビューへのいいね機能のテスト（テストケース一覧 7-1「レビューへのいいねの切り替え」に対応）
 */
class LikeToggleTest extends TestCase
{
    use RefreshDatabase;

    // ※ ビューは Auth::user() のリレーション（favoriteBooks / likedReviews）を参照するため、
    //   操作の前後で画面を確認するときは $user->fresh() でキャッシュされていないユーザーを使う。

    /**
     * 7-1-1 未いいねの状態からいいねできる
     */
    public function test_user_can_like_a_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for(User::factory())->create();

        $this->actingAs($user->fresh())->get(route('books.show', $book))->assertSee('いいね (0)');

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.like', $review));

        $response->assertRedirect(route('books.show', $book));
        $this->assertDatabaseHas('review_likes', ['review_id' => $review->id, 'user_id' => $user->id]);

        // 「いいね済み」の表示に切り替わり、いいね数が1件増える
        $this->actingAs($user->fresh())->get(route('books.show', $book))->assertSee('いいね済み (1)');
    }

    /**
     * 7-1-2 いいね済みの状態からいいねを解除できる
     */
    public function test_user_can_unlike_an_already_liked_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for(User::factory())->create();
        $review->likedByUsers()->attach($user);

        $this->actingAs($user->fresh())->get(route('books.show', $book))->assertSee('いいね済み (1)');

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.like', $review));

        $response->assertRedirect(route('books.show', $book));
        $this->assertDatabaseMissing('review_likes', ['review_id' => $review->id, 'user_id' => $user->id]);

        // 「いいね」の表示に切り替わり、いいね数が1件減る
        $this->actingAs($user->fresh())->get(route('books.show', $book))
            ->assertSee('いいね (0)')
            ->assertDontSee('いいね済み');
    }

    /**
     * 7-1-3 未ログインの場合、いいねボタンを押せない
     */
    public function test_guest_cannot_like_a_review(): void
    {
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for(User::factory())->create();

        // いいねボタン（フォーム）は表示されず、ログインページへのリンクになっている
        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee('action="'.route('reviews.like', $review).'"', false);

        // 直接リクエストを送ってもログインページにリダイレクトされる
        $response = $this->post(route('reviews.like', $review));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('review_likes', 0);
    }
}
