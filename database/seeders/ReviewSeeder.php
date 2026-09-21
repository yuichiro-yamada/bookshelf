<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ReviewSeeder extends Seeder
{
    /**
     * 評価ごとのコメントのテンプレート
     *
     * @var array<int, array<int, string>>
     */
    private const COMMENTS = [
        5 => ['素晴らしい本でした！', '人生が変わりました。', '何度も読み返しています。'],
        4 => ['とても参考になりました。', '読みやすくておすすめです。', '期待通りの内容でした。'],
        3 => ['普通でした。', '可もなく不可もなく。', '期待したほどではなかった。'],
        2 => ['少し期待外れでした。', '内容が薄い印象。', 'もう少し深掘りしてほしかった。'],
        1 => ['残念ながら合いませんでした。', '期待と違いました。'],
    ];

    /**
     * reviewsテーブルにレビューデータを登録する
     *
     * 各書籍に2〜4件のレビューを、ランダムに選んだユーザーが投稿する。
     * 評価は1〜5の全範囲からランダムに決め、コメントは評価に応じたテンプレートから選ぶ。
     * （マイ読書レポートの評価分布グラフが意味のある分布になるようにするため）
     */
    public function run(): void
    {
        $users = User::all();

        Book::all()->each(function (Book $book) use ($users) {
            $reviewCount = random_int(2, 4);

            // 1人のユーザーは1つの書籍に1件しかレビューできないため、重複しないよう選ぶ
            $users->random($reviewCount)->each(function (User $user) use ($book) {
                $rating = random_int(1, 5);

                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'comment' => Arr::random(self::COMMENTS[$rating]),
                ]);
            });
        });
    }
}
