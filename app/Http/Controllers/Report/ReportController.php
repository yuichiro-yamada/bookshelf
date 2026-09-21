<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * マイ読書レポートを表示する
     *
     * ログインユーザー自身のレビューを書籍・ジャンルとあわせて1度に読み込み（N+1回避）、
     * 集計はすべてCollectionメソッドで行う。
     */
    public function index(): View
    {
        /** @var Collection<int, Review> $reviews */
        $reviews = Review::with('book.genres')
            ->where('user_id', Auth::id())
            ->get();

        $stats = [
            'summary' => $this->buildSummary($reviews),
            'rating_distribution' => $this->buildRatingDistribution($reviews),
            'top_rated_books' => $this->buildTopRatedBooks($reviews),
            'genre_ratings' => $this->buildGenreRatings($reviews),
        ];

        return view('reports.index', compact('stats'));
    }

    /**
     * 総レビュー数・読了冊数・平均評価を集計する
     *
     * レビューは1書籍につき1件（reviewsテーブルのuser_id, book_idにユニーク制約）のため、
     * 読了冊数 = レビューを書いた本のユニーク数 = 総レビュー数 と一致する。
     *
     * @param  Collection<int, Review>  $reviews
     * @return array<string, int|float>
     */
    private function buildSummary(Collection $reviews): array
    {
        return [
            'total_reviews' => $reviews->count(),
            'books_read' => $reviews->pluck('book_id')->unique()->count(),
            'average_rating' => $reviews->avg('rating') ?? 0,
        ];
    }

    /**
     * 評価分布（★1〜★5）を集計する
     *
     * キーは0〜4（表示順は★1が上、★5が下）。
     *
     * @param  Collection<int, Review>  $reviews
     * @return Collection<int, int>
     */
    private function buildRatingDistribution(Collection $reviews): Collection
    {
        $ratingCounts = $reviews->countBy('rating');

        return collect(range(1, 5))
            ->mapWithKeys(fn (int $rating) => [$rating - 1 => $ratingCounts->get($rating, 0)]);
    }

    /**
     * 高評価書籍TOP5（評価4以上、評価の高い順・同点は新しいレビュー順）を作る
     *
     * @param  Collection<int, Review>  $reviews
     * @return Collection<int, array<string, int|string>>
     */
    private function buildTopRatedBooks(Collection $reviews): Collection
    {
        return $reviews
            ->filter(fn (Review $review) => $review->rating >= 4)
            ->sortBy([['rating', 'desc'], ['created_at', 'desc']])
            ->take(5)
            ->map(fn (Review $review) => [
                'id' => $review->book->id,
                'title' => $review->book->title,
                'author' => $review->book->author,
                'rating' => $review->rating,
            ])
            ->values();
    }

    /**
     * ジャンル別評価傾向TOP5（平均評価の高い順・同点はレビュー件数順）を作る
     *
     * 1件のレビューは、書籍に紐づく全ジャンルの集計対象になる。
     *
     * @param  Collection<int, Review>  $reviews
     * @return Collection<int, array<string, int|float|string>>
     */
    private function buildGenreRatings(Collection $reviews): Collection
    {
        return $reviews
            ->flatMap(fn (Review $review) => $review->book->genres->map(fn (Genre $genre) => [
                'genre' => $genre,
                'rating' => $review->rating,
            ]))
            ->groupBy(fn (array $row) => $row['genre']->id)
            ->map(fn (Collection $rows) => [
                'id' => $rows->first()['genre']->id,
                'name' => $rows->first()['genre']->name,
                'count' => $rows->count(),
                'average_rating' => (float) $rows->avg('rating'),
            ])
            ->sortBy([['average_rating', 'desc'], ['count', 'desc']])
            ->take(5)
            ->values();
    }
}
