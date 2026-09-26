<?php

namespace Tests\Feature\BookCrud;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍詳細機能のテスト（テストケース一覧 3-2「書籍詳細」に対応）
 *
 * 「編集」「削除」ボタンの有無は、ボタンの文言ではなくリンク先・送信先のURLで判定する
 * （「削除」などの文言はレビューやお気に入りボタンにも含まれうるため）。
 */
class BookShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 3-2-1 必要な情報が表示される（書籍画像、書籍名、著者名、ISBN、出版日、ジャンル、説明）
     */
    public function test_book_detail_shows_all_information(): void
    {
        $genre = Genre::factory()->create(['name' => 'ミステリー']);
        $book = Book::factory()->for(User::factory())->create([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784000000000',
            'published_date' => '2000-01-01',
            'description' => 'これは説明文です。',
            'image_url' => 'https://example.com/cover.jpg',
        ]);
        $book->genres()->attach($genre);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('https://example.com/cover.jpg');
        $response->assertSee('吾輩は猫である');
        $response->assertSee('夏目漱石');
        $response->assertSee('9784000000000');
        $response->assertSee('2000-01-01');
        $response->assertSee('ミステリー');
        $response->assertSee('これは説明文です。');
    }

    /**
     * 3-2-2 説明が登録されていない書籍の場合、説明欄が表示されない
     */
    public function test_description_section_is_hidden_when_not_registered(): void
    {
        $book = Book::factory()->for(User::factory())->create(['description' => null]);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertDontSee('説明:');
    }

    /**
     * 3-2-3 画像URLが登録されていない書籍の場合の表示を確認する
     */
    public function test_shows_no_image_placeholder_when_image_url_is_empty(): void
    {
        $book = Book::factory()->for(User::factory())->create(['image_url' => null]);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('画像なし');
    }

    /**
     * 3-2-4 自分が登録した書籍の場合、「編集」「削除」ボタンが表示される
     */
    public function test_owner_sees_edit_and_delete_buttons(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($owner)->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee(route('books.edit', $book));
        $response->assertSee('action="'.route('books.destroy', $book).'"', false);
    }

    /**
     * 3-2-5 他人が登録した書籍の場合、「編集」「削除」ボタンが表示されない
     */
    public function test_other_user_does_not_see_edit_and_delete_buttons(): void
    {
        $otherUser = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->actingAs($otherUser)->get(route('books.show', $book));

        $response->assertOk();
        $response->assertDontSee(route('books.edit', $book));
        $response->assertDontSee('action="'.route('books.destroy', $book).'"', false);
    }

    /**
     * 3-2-6 未ログインの場合、「編集」「削除」ボタンが表示されない
     */
    public function test_guest_does_not_see_edit_and_delete_buttons(): void
    {
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertDontSee(route('books.edit', $book));
        $response->assertDontSee('action="'.route('books.destroy', $book).'"', false);
    }

    /**
     * 3-2-7 ISBN・出版日が登録されていない書籍の場合の表示を確認する
     */
    public function test_shows_not_registered_when_isbn_and_published_date_are_empty(): void
    {
        $book = Book::factory()->for(User::factory())->create([
            'isbn' => null,
            'published_date' => null,
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSeeInOrder(['ISBN:', '未登録', '出版日:', '未登録'], false);
    }
}
