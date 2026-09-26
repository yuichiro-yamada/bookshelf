<?php

namespace Tests\Feature\Report;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * マイ読書レポートのテスト（テストケース一覧 15-1「読書統計の表示」に対応）
 *
 * 集計値はビューに渡される $stats で確認し、あわせて画面に表示されていることを確認する。
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 指定ユーザーで、新しい書籍に対するレビューを投稿する
     */
    private function review(User $user, int $rating, array $bookAttributes = [], array $reviewAttributes = []): Review
    {
        $book = Book::factory()->create($bookAttributes);

        return Review::factory()->for($user)->for($book)->create(array_merge(['rating' => $rating], $reviewAttributes));
    }

    /**
     * 15-1-1 未ログインの場合、マイ読書レポートページにアクセスできない
     */
    public function test_guest_cannot_access_report(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    /**
     * 15-1-2 レビュー投稿件数・読了冊数・平均評価が正しく集計される
     */
    public function test_summary_is_aggregated_correctly(): void
    {
        $user = User::factory()->create();
        $this->review($user, 3);
        $this->review($user, 4);
        $this->review($user, 5);
        // 他のユーザーのレビューは集計対象外
        $this->review(User::factory()->create(), 1);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['summary']['total_reviews'] === 3
                && $stats['summary']['books_read'] === 3
                && (float) $stats['summary']['average_rating'] === 4.0;
        });
        $response->assertSeeInOrder(['3', '総レビュー数', '3', '読了冊数', '4.0', '平均評価']);
    }

    /**
     * 15-1-3 評価分布(★1〜★5)が正しく表示される
     */
    public function test_rating_distribution_is_correct(): void
    {
        $user = User::factory()->create();
        // ★1:1件, ★2:0件, ★3:2件, ★4:1件, ★5:3件
        foreach ([1, 3, 3, 4, 5, 5, 5] as $rating) {
            $this->review($user, $rating);
        }

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) {
            // キー0〜4がそれぞれ★1〜★5の件数
            return $stats['rating_distribution']->all() === [0 => 1, 1 => 0, 2 => 2, 3 => 1, 4 => 3];
        });
        $response->assertSeeInOrder(['1件', '0件', '2件', '1件', '3件']);
    }

    /**
     * 15-1-4 高評価書籍TOP5(評価4以上)が評価順に表示される
     */
    public function test_top_rated_books_are_listed_in_order(): void
    {
        $user = User::factory()->create();
        $this->review($user, 4, ['title' => '★4・最も古い'], ['created_at' => now()->subDays(6)]);
        $this->review($user, 5, ['title' => '★5・古い'], ['created_at' => now()->subDays(5)]);
        $this->review($user, 4, ['title' => '★4・新しい'], ['created_at' => now()->subDays(1)]);
        $this->review($user, 5, ['title' => '★5・新しい'], ['created_at' => now()->subDays(2)]);
        $this->review($user, 4, ['title' => '★4・中間'], ['created_at' => now()->subDays(3)]);
        $this->review($user, 5, ['title' => '★5・中間'], ['created_at' => now()->subDays(4)]);
        $this->review($user, 3, ['title' => '★3の書籍'], ['created_at' => now()]); // 評価4未満は対象外

        $response = $this->actingAs($user)->get(route('reports.index'));

        $expected = ['★5・新しい', '★5・中間', '★5・古い', '★4・新しい', '★4・中間'];

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats['top_rated_books']->pluck('title')->all() === $expected);
        $response->assertSeeInOrder($expected);
        $response->assertDontSee('★4・最も古い');
        $response->assertDontSee('★3の書籍');
    }

    /**
     * 15-1-5 ジャンル別評価傾向TOP5が平均評価順に表示される
     */
    public function test_genre_ratings_are_listed_in_average_order(): void
    {
        $user = User::factory()->create();
        // ジャンルごとの平均評価: G1=5.0, G2=4.5, G3=4.0, G4=3.0, G5=2.0, G6=1.0（G6は6位のため表示されない）
        $ratingsByGenre = [
            'ジャンル1位' => [5],
            'ジャンル2位' => [5, 4],
            'ジャンル3位' => [4],
            'ジャンル4位' => [3],
            'ジャンル5位' => [2],
            'ジャンル6位' => [1],
        ];
        foreach ($ratingsByGenre as $name => $ratings) {
            $genre = Genre::factory()->create(['name' => $name]);
            foreach ($ratings as $rating) {
                $this->review($user, $rating)->book->genres()->attach($genre);
            }
        }

        $response = $this->actingAs($user)->get(route('reports.index'));

        $expected = ['ジャンル1位', 'ジャンル2位', 'ジャンル3位', 'ジャンル4位', 'ジャンル5位'];

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) use ($expected) {
            return $stats['genre_ratings']->pluck('name')->all() === $expected
                && $stats['genre_ratings']->pluck('average_rating')->all() === [5.0, 4.5, 4.0, 3.0, 2.0];
        });
        $response->assertSeeInOrder($expected);
        $response->assertDontSee('ジャンル6位');
    }

    /**
     * 15-1-6 レビューを1件も投稿していない場合の表示を確認する
     */
    public function test_report_for_user_without_reviews(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['summary']['total_reviews'] === 0
                && $stats['summary']['books_read'] === 0
                && $stats['summary']['average_rating'] == 0
                && $stats['top_rated_books']->isEmpty()
                && $stats['genre_ratings']->isEmpty();
        });
        $response->assertSee('4星以上の書籍がありません');
        $response->assertSee('ジャンルが設定された書籍のレビューがありません');
    }
}
