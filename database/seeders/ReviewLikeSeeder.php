<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * review_likesテーブルに各レビュー0〜3人のいいねデータを登録する（自分のレビューは除く）
     */
    public function run(): void
    {
        $userIds = User::pluck('id');

        Review::all()->each(function (Review $review) use ($userIds) {
            $candidateIds = $userIds
                ->reject(fn ($id) => (int) $id === (int) $review->user_id)
                ->shuffle();

            $likerIds = $candidateIds->take(random_int(0, 3));

            $review->likedByUsers()->syncWithoutDetaching($likerIds);
        });
    }
}
