<?php

namespace App\Http\Controllers\Ranking;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * 評価ランキング（上位10件）を表示する
     */
    public function index(): View
    {
        $rankedBooks = Book::withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->whereHas('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->orderByDesc('published_date')
            ->take(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }
}
