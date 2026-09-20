<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍詳細機能のテスト（テストケース一覧「書籍詳細」に対応）
 */
class BookShowTest extends TestCase
{
    use RefreshDatabase;

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
        $response->assertSee('吾輩は猫である');
        $response->assertSee('夏目漱石');
        $response->assertSee('9784000000000');
        $response->assertSee('ミステリー');
        $response->assertSee('これは説明文です。');
    }

    public function test_description_section_is_hidden_when_not_registered(): void
    {
        $book = Book::factory()->for(User::factory())->create(['description' => null]);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertDontSee('説明:');
    }

    public function test_shows_no_image_placeholder_when_image_url_is_empty(): void
    {
        $book = Book::factory()->for(User::factory())->create(['image_url' => null]);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('画像なし');
    }

    public function test_owner_sees_edit_and_delete_buttons(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($owner)->get(route('books.show', $book));

        $response->assertSee('編集');
        $response->assertSee('削除');
    }

    public function test_other_user_does_not_see_edit_and_delete_buttons(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->get(route('books.show', $book));

        $response->assertDontSee('編集');
        $response->assertDontSee('削除');
    }

    public function test_guest_does_not_see_edit_and_delete_buttons(): void
    {
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->get(route('books.show', $book));

        $response->assertDontSee('編集');
        $response->assertDontSee('削除');
    }
}
