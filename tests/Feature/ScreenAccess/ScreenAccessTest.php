<?php

namespace Tests\Feature\ScreenAccess;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 主要画面のアクセス制御のテスト（テストケース一覧 2-1「主要画面のアクセス制御」に対応）
 */
class ScreenAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 2-1-1 書籍一覧ページ(トップページ)は未ログインでも閲覧できる
     */
    public function test_guest_can_view_book_list(): void
    {
        Book::factory()->create(['title' => 'トップページの書籍']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('トップページの書籍');
    }

    /**
     * 2-1-2 書籍詳細ページは未ログインでも閲覧できる
     */
    public function test_guest_can_view_book_detail(): void
    {
        $book = Book::factory()->create(['title' => '詳細ページの書籍']);

        $response = $this->get("/books/{$book->id}");

        $response->assertOk();
        $response->assertSee('詳細ページの書籍');
    }

    /**
     * 2-1-3 ジャンル一覧ページは未ログインだとアクセスできない
     */
    public function test_guest_cannot_view_genre_list(): void
    {
        $this->get('/genres')->assertRedirect(route('login'));
    }

    /**
     * 2-1-4 ジャンル詳細ページは未ログインだとアクセスできない
     */
    public function test_guest_cannot_view_genre_detail(): void
    {
        $genre = Genre::factory()->create();

        $this->get("/genres/{$genre->id}")->assertRedirect(route('login'));
    }

    /**
     * 2-1-5 ランキングページは未ログインでも閲覧できる
     */
    public function test_guest_can_view_ranking(): void
    {
        $book = Book::factory()->create(['title' => 'ランキングの書籍']);
        Review::factory()->for($book)->create(['rating' => 5]);

        $response = $this->get('/ranking');

        $response->assertOk();
        $response->assertSee('ランキングの書籍');
    }

    /**
     * 2-1-6 書籍登録ページは未ログインだとアクセスできない
     */
    public function test_guest_cannot_view_book_create_page(): void
    {
        $this->get('/books/create')->assertRedirect(route('login'));
    }

    /**
     * 2-1-7 書籍編集ページは未ログインだとアクセスできない
     */
    public function test_guest_cannot_view_book_edit_page(): void
    {
        $book = Book::factory()->create();

        $this->get("/books/{$book->id}/edit")->assertRedirect(route('login'));
    }

    /**
     * 2-1-8 ジャンル登録ページは未ログインだとアクセスできない
     */
    public function test_guest_cannot_view_genre_create_page(): void
    {
        $this->get('/genres/create')->assertRedirect(route('login'));
    }

    /**
     * 2-1-9 ジャンル編集ページは未ログインだとアクセスできない
     */
    public function test_guest_cannot_view_genre_edit_page(): void
    {
        $genre = Genre::factory()->create();

        $this->get("/genres/{$genre->id}/edit")->assertRedirect(route('login'));
    }

    /**
     * 2-1-10 お気に入り一覧ページは未ログインだとアクセスできない
     */
    public function test_guest_cannot_view_favorite_list(): void
    {
        $this->get('/favorites')->assertRedirect(route('login'));
    }

    /**
     * 2-1-11 マイ読書レポートページは未ログインだとアクセスできない
     */
    public function test_guest_cannot_view_report(): void
    {
        $this->get('/reports')->assertRedirect(route('login'));
    }

    /**
     * 2-1-12 読書計画一覧・登録ページは未ログインだとアクセスできない
     */
    public function test_guest_cannot_view_reading_plan_pages(): void
    {
        $this->get('/reading-plans')->assertRedirect(route('login'));
        $this->get('/reading-plans/create')->assertRedirect(route('login'));
    }

    /**
     * 2-1-13 通知一覧ページは未ログインだとアクセスできない
     */
    public function test_guest_cannot_view_notification_list(): void
    {
        $this->get('/notifications')->assertRedirect(route('login'));
    }

    /**
     * 2-1-14 ログイン中のユーザーが会員登録・ログインページにアクセスするとトップページへリダイレクトされる
     */
    public function test_logged_in_user_is_redirected_from_register_and_login_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/register')->assertRedirect('/');
        $this->actingAs($user)->get('/login')->assertRedirect('/');
    }
}
