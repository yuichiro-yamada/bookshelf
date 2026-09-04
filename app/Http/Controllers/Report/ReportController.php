<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * マイ読書レポートを表示する
     */
    public function index(): View
    {
        $userId = Auth::id();

        $reviews = Review::where('user_id', $userId)->get();

        $summary = [
            'total_reviews' => $reviews->count(),
            'books_read' => ReadingPlan::where('user_id', $userId)
                ->whereNotNull('completed_at')
                ->pluck('book_id')
                ->unique()
                ->count(),
            'average_rating' => $reviews->avg('rating') ?? 0,
        ];

        // 評価分布（★1〜★5、インデックス0〜4）
        $ratingCounts = $reviews->countBy('rating');
        $ratingDistribution = collect(range(5, 1))
            ->mapWithKeys(fn (int $rating) => [$rating - 1 => $ratingCounts->get($rating, 0)]);

        // 高評価書籍TOP5（4以上）
        $topRatedBooks = Review::with('book')
            ->where('user_id', $userId)
            ->where('rating', '>=', 4)
            ->orderByDesc('rating')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn (Review $review) => [
                'id' => $review->book->id,
                'title' => $review->book->title,
                'author' => $review->book->author,
                'rating' => $review->rating,
            ]);

        // ジャンル別評価傾向TOP5（平均評価が高い順）
        $genreRatings = Genre::query()
            ->join('book_genres', 'genres.id', '=', 'book_genres.genre_id')
            ->join('books', 'books.id', '=', 'book_genres.book_id')
            ->join('reviews', 'reviews.book_id', '=', 'books.id')
            ->where('reviews.user_id', $userId)
            ->selectRaw('genres.id as id, genres.name as name, COUNT(reviews.id) as count, AVG(reviews.rating) as average_rating')
            ->groupBy('genres.id', 'genres.name')
            ->orderByDesc('average_rating')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'count' => (int) $row->count,
                'average_rating' => (float) $row->average_rating,
            ]);

        $stats = [
            'summary' => $summary,
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}
