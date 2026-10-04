<?php

namespace Tests\Feature\Genre;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ジャンル一覧機能のテスト（テストケース一覧 5-1「ジャンル一覧」に対応）
 *
 * ジャンル一覧・詳細はログイン必須（機能要件）。
 */
class GenreIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 5-1-1 ジャンル一覧ページを表示する
     */
    public function test_genre_list_shows_name_and_book_count(): void
    {
        $user = User::factory()->create();
        $mystery = Genre::factory()->create(['name' => 'ミステリー']);
        $sf = Genre::factory()->create(['name' => 'SF']);
        Book::factory()->for($user)->count(2)->create()->each(fn (Book $book) => $book->genres()->attach($mystery));

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['ミステリー', '2冊']);
        $response->assertSeeInOrder(['SF', '0冊']);
    }

    /**
     * 5-1-2 ジャンルが1件も登録されていない場合の表示を確認する
     */
    public function test_shows_message_when_no_genres_are_registered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertOk();
        $response->assertSee('ジャンルが登録されていません。');
    }

    /**
     * 5-1-3 未ログインの場合、ジャンル一覧ページにアクセスできない
     */
    public function test_guest_cannot_access_genre_list(): void
    {
        $response = $this->get(route('genres.index'));

        $response->assertRedirect(route('login'));
    }
}
