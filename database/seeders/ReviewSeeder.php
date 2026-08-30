<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * reviewsテーブルにレビューデータを32件登録する
     *
     * 5人のユーザーが11冊の書籍に対してレビューを投稿する。
     * 各書籍に2〜4件、ratingは3〜5の範囲で配分する。
     */
    public function run(): void
    {
        $users = User::all()->keyBy('email');
        $books = Book::all()->keyBy('isbn');

        $reviews = [
            // 吾輩は猫である（3件）
            ['isbn' => '9784101010014', 'email' => 'yamada@example.com', 'rating' => 5, 'comment' => '猫の視点から人間社会を皮肉る語り口が面白く、何度読んでも新しい発見があります。'],
            ['isbn' => '9784101010014', 'email' => 'suzuki@example.com', 'rating' => 4, 'comment' => '文体は少し古めですが、ユーモアのセンスは今読んでも十分に楽しめました。'],
            ['isbn' => '9784101010014', 'email' => 'tanaka@example.com', 'rating' => 4, 'comment' => 'テンポよく読める一方で、当時の社会背景を知っているとより楽しめると思います。'],

            // 人を動かす（3件）
            ['isbn' => '9784422100524', 'email' => 'suzuki@example.com', 'rating' => 5, 'comment' => '人間関係の本質を突いた内容で、仕事だけでなく日常生活にも役立つ一冊でした。'],
            ['isbn' => '9784422100524', 'email' => 'sato@example.com', 'rating' => 4, 'comment' => '具体例が多く読みやすいですが、少し繰り返しが多く感じる部分もありました。'],
            ['isbn' => '9784422100524', 'email' => 'takahashi@example.com', 'rating' => 5, 'comment' => '古典的な名著と言われる理由がよくわかる、実践的なアドバイスばかりでした。'],

            // リーダブルコード（4件）
            ['isbn' => '9784873115658', 'email' => 'tanaka@example.com', 'rating' => 5, 'comment' => 'コードレビューで指摘されがちなポイントが具体例付きでまとまっていて実務にすぐ活かせました。'],
            ['isbn' => '9784873115658', 'email' => 'yamada@example.com', 'rating' => 4, 'comment' => '初心者にも読みやすい構成で、命名や関数分割の基本を学び直せました。'],
            ['isbn' => '9784873115658', 'email' => 'takahashi@example.com', 'rating' => 5, 'comment' => 'エンジニアなら一度は読むべき定番書だと感じました。'],
            ['isbn' => '9784873115658', 'email' => 'suzuki@example.com', 'rating' => 4, 'comment' => 'サンプルコードが豊富で理解しやすかったです。'],

            // 7つの習慣（3件）
            ['isbn' => '9784863940246', 'email' => 'sato@example.com', 'rating' => 5, 'comment' => '自己啓発書の中でも特に体系的にまとまっていて、繰り返し読み返したくなる内容でした。'],
            ['isbn' => '9784863940246', 'email' => 'yamada@example.com', 'rating' => 3, 'comment' => '内容は良いのですが、やや抽象的な部分もあり実践には工夫が必要だと感じました。'],
            ['isbn' => '9784863940246', 'email' => 'tanaka@example.com', 'rating' => 4, 'comment' => '第一の習慣から順に実践していくことで着実に変化を感じられました。'],

            // 坊っちゃん（2件）
            ['isbn' => '9784101010021', 'email' => 'suzuki@example.com', 'rating' => 4, 'comment' => '主人公の江戸っ子気質な性格が痛快で、テンポよく読み進められました。'],
            ['isbn' => '9784101010021', 'email' => 'takahashi@example.com', 'rating' => 5, 'comment' => '短編ながら人物描写が生き生きとしていて、何度読んでも飽きません。'],

            // サピエンス全史（3件）
            ['isbn' => '9784309226712', 'email' => 'yamada@example.com', 'rating' => 5, 'comment' => '人類の歴史を壮大なスケールで描いていて、読み終えた後に世界の見方が変わりました。'],
            ['isbn' => '9784309226712', 'email' => 'sato@example.com', 'rating' => 4, 'comment' => '情報量が多く読み応えがありますが、その分学びも多い一冊です。'],
            ['isbn' => '9784309226712', 'email' => 'tanaka@example.com', 'rating' => 5, 'comment' => '農業革命や科学革命の捉え方が新鮮で、知的好奇心が刺激されました。'],

            // Clean Code（4件）
            ['isbn' => '9784048930598', 'email' => 'takahashi@example.com', 'rating' => 5, 'comment' => '実務で悩んでいた設計の指針が明確になり、非常に参考になりました。'],
            ['isbn' => '9784048930598', 'email' => 'suzuki@example.com', 'rating' => 4, 'comment' => '少し厳格すぎると感じる部分もありましたが、基本原則としては納得できました。'],
            ['isbn' => '9784048930598', 'email' => 'yamada@example.com', 'rating' => 4, 'comment' => '命名規則や関数の書き方について具体的な指針が得られました。'],
            ['isbn' => '9784048930598', 'email' => 'sato@example.com', 'rating' => 3, 'comment' => '内容は良いですが、翻訳の言い回しがやや読みにくい箇所がありました。'],

            // 嫌われる勇気（2件）
            ['isbn' => '9784478025819', 'email' => 'tanaka@example.com', 'rating' => 5, 'comment' => '対話形式で読みやすく、アドラー心理学の考え方がすっと入ってきました。'],
            ['isbn' => '9784478025819', 'email' => 'suzuki@example.com', 'rating' => 4, 'comment' => '承認欲求について考えさせられる内容で、読後感がとても良かったです。'],

            // 火花（3件）
            ['isbn' => '9784163902302', 'email' => 'sato@example.com', 'rating' => 4, 'comment' => '芸人という特殊な世界を通して人間の本質が描かれていて引き込まれました。'],
            ['isbn' => '9784163902302', 'email' => 'takahashi@example.com', 'rating' => 3, 'comment' => '文学的な表現が多く、好みが分かれる作品だと感じました。'],
            ['isbn' => '9784163902302', 'email' => 'yamada@example.com', 'rating' => 4, 'comment' => '先輩と後輩の関係性の描写が印象に残る一冊でした。'],

            // FACTFULNESS（3件）
            ['isbn' => '9784822289607', 'email' => 'suzuki@example.com', 'rating' => 5, 'comment' => '思い込みを覆すデータが多く、世界の見方がアップデートされる感覚がありました。'],
            ['isbn' => '9784822289607', 'email' => 'tanaka@example.com', 'rating' => 4, 'comment' => 'クイズ形式で始まる構成が面白く、最後まで飽きずに読めました。'],
            ['isbn' => '9784822289607', 'email' => 'takahashi@example.com', 'rating' => 5, 'comment' => 'ニュースの受け取り方について改めて考えさせられる良書でした。'],

            // コンテナ物語（2件）
            ['isbn' => '9784822251468', 'email' => 'yamada@example.com', 'rating' => 4, 'comment' => 'コンテナという地味なテーマが世界経済を変えた過程が丁寧に描かれていて驚きました。'],
            ['isbn' => '9784822251468', 'email' => 'sato@example.com', 'rating' => 4, 'comment' => '物流業界の歴史を知る上でとても勉強になる一冊でした。'],
        ];

        foreach ($reviews as $data) {
            Review::create([
                'user_id' => $users[$data['email']]->id,
                'book_id' => $books[$data['isbn']]->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'],
            ]);
        }
    }
}
