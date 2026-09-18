<?php

namespace Tests\Feature\Like;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * レビューへのいいねの切り替えのテスト（テストケース一覧「レビューへのいいねの切り替え」に対応）
 */
class LikeToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_like_a_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for(User::factory())->create();

        $response = $this->actingAs($user)->post(route('reviews.like', $review));

        $response->assertRedirect();
        $this->assertDatabaseHas('review_likes', ['review_id' => $review->id, 'user_id' => $user->id]);
    }

    public function test_user_can_unlike_an_already_liked_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for(User::factory())->create();
        $review->likedByUsers()->attach($user);

        $response = $this->actingAs($user)->post(route('reviews.like', $review));

        $response->assertRedirect();
        $this->assertDatabaseMissing('review_likes', ['review_id' => $review->id, 'user_id' => $user->id]);
    }
}
