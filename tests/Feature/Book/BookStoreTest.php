<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍登録機能のテスト（テストケース一覧「書籍登録」に対応）
 */
class BookStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * バリデーションを通過するための正しい入力値
     */
    private function validPayload(array $overrides = []): array
    {
        $genre = Genre::factory()->create();

        return array_merge([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784000000000',
            'published_date' => '2000-01-01',
            'description' => '説明文です。',
            'image_url' => 'https://example.com/cover.jpg',
            'genres' => [$genre->id],
        ], $overrides);
    }

    public function test_guest_cannot_access_book_create_page(): void
    {
        $response = $this->get(route('books.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_title_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['title' => '']));

        $response->assertSessionHasErrors(['title' => '書籍タイトルを入力してください']);
    }

    public function test_title_must_not_exceed_255_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['title' => str_repeat('あ', 256)]));

        $response->assertSessionHasErrors(['title' => '書籍タイトルは255文字以内で入力してください']);
    }

    public function test_duplicate_title_is_allowed(): void
    {
        $user = User::factory()->create();
        Book::factory()->for($user)->create(['title' => '既存の書籍']);

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['title' => '既存の書籍']));

        $response->assertRedirect(route('books.index'));
        $response->assertSessionDoesntHaveErrors(['title']);
        $this->assertDatabaseCount('books', 2);
    }

    public function test_author_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['author' => '']));

        $response->assertSessionHasErrors(['author' => '著者名を入力してください']);
    }

    public function test_book_can_be_registered_without_isbn(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['title' => 'ISBNなしの書籍', 'isbn' => '']));

        $response->assertRedirect(route('books.index'));
        $response->assertSessionDoesntHaveErrors(['isbn']);
        $this->assertDatabaseHas('books', ['title' => 'ISBNなしの書籍', 'isbn' => null]);
    }

    public function test_isbn_must_be_13_digit_number(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['isbn' => '123ABC']));

        $response->assertSessionHasErrors(['isbn' => 'ISBNは13桁の数字で入力してください']);
    }

    public function test_isbn_must_be_unique(): void
    {
        $user = User::factory()->create();
        Book::factory()->for($user)->create(['isbn' => '9784000000000']);

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['isbn' => '9784000000000']));

        $response->assertSessionHasErrors(['isbn' => 'このISBNはすでに登録されています']);
    }

    public function test_book_can_be_registered_without_published_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['title' => '出版日なしの書籍', 'published_date' => '']));

        $response->assertRedirect(route('books.index'));
        $response->assertSessionDoesntHaveErrors(['published_date']);
        $this->assertDatabaseHas('books', ['title' => '出版日なしの書籍', 'published_date' => null]);
    }

    public function test_description_must_not_exceed_1000_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['description' => str_repeat('あ', 1001)]));

        $response->assertSessionHasErrors(['description' => '説明は1000文字以内で入力してください']);
    }

    public function test_image_url_must_be_valid_url_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['image_url' => 'not-a-url']));

        $response->assertSessionHasErrors(['image_url' => 'URL形式で入力してください']);
    }

    public function test_at_least_one_genre_must_be_selected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['genres' => []]));

        $response->assertSessionHasErrors(['genres' => 'ジャンルを1つ以上選択してください']);
    }

    public function test_book_is_registered_with_valid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['title' => '新しい書籍']));

        $response->assertRedirect(route('books.index'));
        $response->assertSessionHas('success', '「新しい書籍」を登録しました。');
        $this->assertDatabaseHas('books', ['title' => '新しい書籍', 'user_id' => $user->id]);
    }
}
