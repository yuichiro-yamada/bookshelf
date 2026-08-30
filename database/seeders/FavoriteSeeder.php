<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * favoritesテーブルに各ユーザー3〜5冊のお気に入りを登録する
     */
    public function run(): void
    {
        $books = Book::all()->keyBy('title');

        $favorites = [
            'yamada@example.com' => ['吾輩は猫である', 'リーダブルコード', 'サピエンス全史', '火花'],
            'suzuki@example.com' => ['人を動かす', '7つの習慣', 'Clean Code'],
            'tanaka@example.com' => ['リーダブルコード', '坊っちゃん', '嫌われる勇気', 'FACTFULNESS', 'コンテナ物語'],
            'sato@example.com' => ['吾輩は猫である', '7つの習慣', 'サピエンス全史', '火花', 'FACTFULNESS'],
            'takahashi@example.com' => ['人を動かす', 'Clean Code', 'コンテナ物語'],
        ];

        foreach ($favorites as $email => $titles) {
            $user = User::where('email', $email)->first();
            $bookIds = $books->only($titles)->pluck('id');

            $user->favoriteBooks()->syncWithoutDetaching($bookIds);
        }
    }
}
