<?php

namespace Tests\Feature\BookCrud;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍登録機能のテスト（テストケース一覧 3-3「書籍登録」に対応）
 */
class BookStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * バリデーションを通過するための正しい入力値
     *
     * 画面のフォームは全項目を送信するため、任意項目も空文字列として含めている。
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

    /**
     * 3-3-1 未ログインの場合、書籍登録ページにアクセスできない
     */
    public function test_guest_cannot_access_book_create_page(): void
    {
        $response = $this->get(route('books.create'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 3-3-2 タイトルが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_title_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['title' => '']));

        $response->assertSessionHasErrors(['title' => '書籍タイトルを入力してください']);
        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 3-3-3 タイトルが256文字以上の場合、バリデーションメッセージが表示される
     */
    public function test_title_must_not_exceed_255_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['title' => str_repeat('あ', 256)]));

        $response->assertSessionHasErrors(['title' => '書籍タイトルは255文字以内で入力してください']);
    }

    /**
     * 3-3-4 既に登録されているタイトルと同じタイトルでも、書籍を登録できる
     */
    public function test_duplicate_title_is_allowed(): void
    {
        $user = User::factory()->create();
        Book::factory()->for($user)->create(['title' => '既存の書籍']);

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['title' => '既存の書籍']));

        $response->assertRedirect(route('books.index'));
        $response->assertSessionDoesntHaveErrors(['title']);
        $this->assertSame(2, Book::where('title', '既存の書籍')->count());
    }

    /**
     * 3-3-5 著者名が入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_author_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['author' => '']));

        $response->assertSessionHasErrors(['author' => '著者名を入力してください']);
    }

    /**
     * 3-3-6 ISBNが入力されていなくても、書籍を登録できる
     */
    public function test_book_can_be_registered_without_isbn(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['title' => 'ISBNなしの書籍', 'isbn' => '']));

        $response->assertRedirect(route('books.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('books', ['title' => 'ISBNなしの書籍', 'isbn' => null]);

        // ISBNの項目自体を送信しない場合も登録できる
        $payload = $this->validPayload(['title' => 'ISBN項目なしの書籍']);
        unset($payload['isbn']);

        $this->actingAs($user)->post(route('books.store'), $payload)
            ->assertRedirect(route('books.index'))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('books', ['title' => 'ISBN項目なしの書籍', 'isbn' => null]);
    }

    /**
     * 3-3-7 ISBNが13桁の数字でない場合、バリデーションメッセージが表示される
     */
    public function test_isbn_must_be_13_digit_number(): void
    {
        $user = User::factory()->create();

        // 13桁でない（12桁）
        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['isbn' => '978400000000']));
        $response->assertSessionHasErrors(['isbn' => 'ISBNは13桁の数字で入力してください']);

        // 数字以外を含む（13文字）
        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['isbn' => '978400000000X']));
        $response->assertSessionHasErrors(['isbn' => 'ISBNは13桁の数字で入力してください']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 3-3-8 既に登録されているISBNの場合、バリデーションメッセージが表示される
     */
    public function test_isbn_must_be_unique(): void
    {
        $user = User::factory()->create();
        Book::factory()->for($user)->create(['isbn' => '9784000000000']);

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['isbn' => '9784000000000']));

        $response->assertSessionHasErrors(['isbn' => 'このISBNはすでに登録されています']);
    }

    /**
     * 3-3-9 出版日が入力されていなくても、書籍を登録できる
     */
    public function test_book_can_be_registered_without_published_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['title' => '出版日なしの書籍', 'published_date' => '']));

        $response->assertRedirect(route('books.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('books', ['title' => '出版日なしの書籍', 'published_date' => null]);

        // 出版日の項目自体を送信しない場合も登録できる
        // （ISBNは重複不可のため、1件目とは別のISBNを指定する）
        $payload = $this->validPayload(['title' => '出版日項目なしの書籍', 'isbn' => '9784000000001']);
        unset($payload['published_date']);

        $this->actingAs($user)->post(route('books.store'), $payload)
            ->assertRedirect(route('books.index'))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('books', ['title' => '出版日項目なしの書籍', 'published_date' => null]);
    }

    /**
     * 3-3-10 説明が1001文字以上の場合、バリデーションメッセージが表示される
     */
    public function test_description_must_not_exceed_1000_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['description' => str_repeat('あ', 1001)]));

        $response->assertSessionHasErrors(['description' => '説明は1000文字以内で入力してください']);
    }

    /**
     * 3-3-11 画像URLがURL形式でない場合、バリデーションメッセージが表示される
     */
    public function test_image_url_must_be_valid_url_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['image_url' => 'not-a-url']));

        $response->assertSessionHasErrors(['image_url' => 'URL形式で入力してください']);
    }

    /**
     * 3-3-12 ジャンルが1つも選択されていない場合、バリデーションメッセージが表示される
     */
    public function test_at_least_one_genre_must_be_selected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validPayload(['genres' => []]));

        $response->assertSessionHasErrors(['genres' => 'ジャンルを1つ以上選択してください']);
    }

    /**
     * 3-3-13 全ての項目が入力されている場合、書籍情報が登録される
     */
    public function test_book_is_registered_with_valid_data(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload(['title' => '新しい書籍']);

        $response = $this->actingAs($user)->post(route('books.store'), $payload);

        $response->assertRedirect(route('books.index'));
        $response->assertSessionHas('success', '「新しい書籍」を登録しました。');

        $book = Book::where('title', '新しい書籍')->firstOrFail();
        $this->assertSame($user->id, $book->user_id);
        $this->assertSame('夏目漱石', $book->author);
        $this->assertSame('9784000000000', $book->isbn);
        $this->assertSame('2000-01-01', $book->published_date->format('Y-m-d'));
        $this->assertSame('説明文です。', $book->description);
        $this->assertSame('https://example.com/cover.jpg', $book->image_url);
        $this->assertDatabaseHas('book_genre', ['book_id' => $book->id, 'genre_id' => $payload['genres'][0]]);

        // 遷移先の書籍一覧画面にメッセージが表示される
        $this->get(route('books.index'))->assertSee('「新しい書籍」を登録しました。');
    }

    /**
     * 3-3-14 ISBN・出版日の入力欄は任意項目として表示される
     */
    public function test_isbn_and_published_date_are_displayed_as_optional(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('books.create'));

        $response->assertOk();
        $content = $response->getContent();
        $requiredMark = '\\s*<span class="text-red-500">\\*<\\/span>\\s*<\\/label>/';

        // 必須項目（タイトル・著者・ジャンル）には必須マークが表示される
        $this->assertMatchesRegularExpression('/タイトル'.$requiredMark, $content);
        $this->assertMatchesRegularExpression('/著者'.$requiredMark, $content);
        $this->assertMatchesRegularExpression('/ジャンル'.$requiredMark, $content);

        // ISBN・出版日には必須マークが表示されない
        $this->assertMatchesRegularExpression('/ISBN-13\\s*<\\/label>/', $content);
        $this->assertMatchesRegularExpression('/出版日\\s*<\\/label>/', $content);
    }
}
