<?php

namespace Tests\Feature\Genre;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ジャンル詳細機能のテスト（テストケース一覧 5-3「ジャンル詳細」に対応）
 */
class GenreShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 5-3-1 ジャンルに紐づく書籍が一覧表示される
     */
    public function test_books_linked_to_genre_are_listed(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $otherGenre = Genre::factory()->create();
        $linkedBook = Book::factory()->for($user)->create(['title' => 'ジャンル紐づき書籍']);
        $linkedBook->genres()->attach($genre);
        $otherBook = Book::factory()->for($user)->create(['title' => '別ジャンルの書籍']);
        $otherBook->genres()->attach($otherGenre);

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertSee('ジャンル紐づき書籍');
        $response->assertDontSee('別ジャンルの書籍');
    }

    /**
     * 5-3-2 ジャンルに紐づく書籍が1件もない場合の表示を確認する
     */
    public function test_shows_message_when_no_books_are_linked(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertSee('このジャンルの書籍はまだ登録されていません。');
    }

    /**
     * 5-3-3 ジャンル一覧から遷移した場合、「ジャンル一覧に戻る」リンクが表示される
     */
    public function test_back_link_goes_to_genre_list_when_coming_from_genre_list(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        // ジャンル一覧のリンクには、遷移元（from=genres）が付いている
        $showUrl = route('genres.show', [$genre, 'from' => 'genres']);
        $this->actingAs($user)->get(route('genres.index'))
            ->assertOk()
            ->assertSee('href="'.e($showUrl).'"', false);

        $response = $this->actingAs($user)->get($showUrl);

        $response->assertOk();
        $response->assertSee('href="'.route('genres.index').'"', false);
        $response->assertSee('← ジャンル一覧に戻る');
        $response->assertDontSee('マイ読書レポートに戻る');

        // 遷移元の指定がない場合も、ジャンル一覧に戻る
        $this->actingAs($user)->get(route('genres.show', $genre))
            ->assertOk()
            ->assertSee('← ジャンル一覧に戻る');
    }

    /**
     * 5-3-4 マイ読書レポートから遷移した場合、「マイ読書レポートに戻る」リンクが表示される
     */
    public function test_back_link_goes_to_report_when_coming_from_report(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();
        $book->genres()->attach($genre);
        Review::factory()->for($user)->for($book)->create(['rating' => 5]);

        // マイ読書レポートのリンクには、遷移元（from=reports）が付いている
        $showUrl = route('genres.show', [$genre->id, 'from' => 'reports']);
        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('href="'.e($showUrl).'"', false);

        $response = $this->actingAs($user)->get($showUrl);

        $response->assertOk();
        $response->assertSee('href="'.route('reports.index').'"', false);
        $response->assertSee('← マイ読書レポートに戻る');
        $response->assertDontSee('ジャンル一覧に戻る');
    }

    /**
     * 5-3-5 ページを送っても、遷移元に応じた「戻る」リンクが維持される
     */
    public function test_back_link_is_kept_after_paging(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        Book::factory()->count(11)->create()->each(fn (Book $book) => $book->genres()->attach($genre));

        $firstPage = $this->actingAs($user)->get(route('genres.show', [$genre, 'from' => 'reports']));

        // ページネーションのリンクに from=reports が引き継がれている
        $firstPage->assertOk();
        $firstPage->assertViewHas('books', fn ($books) => str_contains($books->nextPageUrl(), 'from=reports'));

        $this->actingAs($user)->get(route('genres.show', [$genre, 'from' => 'reports', 'page' => 2]))
            ->assertOk()
            ->assertSee('← マイ読書レポートに戻る');
    }

    /**
     * 5-3-6 ジャンルに紐づく書籍が登録日時の新しい順に表示される
     */
    public function test_books_are_listed_in_newest_order(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $old = Book::factory()->create(['title' => '古い書籍', 'created_at' => now()->subDays(2)]);
        $new = Book::factory()->create(['title' => '新しい書籍', 'created_at' => now()]);
        $middle = Book::factory()->create(['title' => '中間の書籍', 'created_at' => now()->subDay()]);
        // ジャンルへの紐づけ順（中間テーブルの登録順）とは関係なく、書籍の登録日時で並ぶ
        $genre->books()->attach([$new->id, $old->id, $middle->id]);

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertViewHas('books', fn ($books) => $books->pluck('title')->all() === ['新しい書籍', '中間の書籍', '古い書籍']);
        $response->assertSeeInOrder(['新しい書籍', '中間の書籍', '古い書籍']);
    }
}
