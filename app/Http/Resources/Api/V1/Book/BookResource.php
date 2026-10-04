<?php

namespace App\Http\Resources\Api\V1\Book;

use App\Http\Resources\Api\V1\Genre\GenreResource;
use App\Http\Resources\Api\V1\Review\ReviewResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * リソースを配列に変換する
     *
     * average_rating・review_count は、コントローラー側で
     * withAvg('reviews', 'rating') / withCount('reviews')
     * （もしくは loadAvg / loadCount）しておくことを前提にしている。
     *
     * reviews は書籍詳細（show）でのみ genres.reviews.user を
     * eager load した場合に含まれ、一覧（index）では含まれない。
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date?->format('Y-m-d'),
            'description' => $this->description,
            'image_url' => $this->image_url,
            'user_id' => $this->user_id,
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            // 小数第1位で丸める（レビューが1件もない場合は null）
            'average_rating' => $this->reviews_avg_rating !== null
                ? round((float) $this->reviews_avg_rating, 1)
                : null,
            'review_count' => (int) $this->reviews_count,
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
