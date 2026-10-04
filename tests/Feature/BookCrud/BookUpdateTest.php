<?php

namespace Tests\Feature\BookCrud;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍編集機能のテスト（テストケース一覧 3-4「書籍編集」に対応）
 */
class BookUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * バリデーションを通過するための正しい入力値
     */
    private function validPayload(Genre $genre, array $overrides = []): array
    {
        return array_merge([
            'title' => '更新後タイトル',
            'author' => '更新後の著者',
            'isbn' => '9784000000009',
            'published_date' => '2001-01-01',
            'description' => '更新後の説明',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$genre->id],
        ], $overrides);
    }

    /**
     * 3-4-1 未ログインの場合、書籍編集ページにアクセスできない
     */
    public function test_guest_cannot_access_book_edit_page(): void
    {
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->get(route('books.edit', $book));

        $response->assertRedirect(route('login'));
    }

    /**
     * 3-4-2 他人が登録した書籍は編集できない
     */
    public function test_other_user_cannot_edit_book(): void
    {
        $otherUser = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->actingAs($otherUser)->get(route('books.edit', $book));

        $response->assertForbidden();
    }

    /**
     * 3-4-3 編集画面を開くと現在の書籍情報が初期表示される
     */
    public function test_edit_page_shows_current_book_information(): void
    {
        $owner = User::factory()->create();
        $selectedGenre = Genre::factory()->create(['name' => '選択済みジャンル']);
        $otherGenre = Genre::factory()->create(['name' => '未選択ジャンル']);
        $book = Book::factory()->for($owner)->create([
            'title' => '編集前タイトル',
            'author' => '編集前の著者',
            'isbn' => '9784000000002',
            'published_date' => '2000-01-01',
            'description' => '編集前の説明',
            'image_url' => 'https://example.com/before.jpg',
        ]);
        $book->genres()->attach($selectedGenre);

        $response = $this->actingAs($owner)->get(route('books.edit', $book));

        $response->assertOk();
        $response->assertSee('value="編集前タイトル"', false);
        $response->assertSee('value="編集前の著者"', false);
        $response->assertSee('value="9784000000002"', false);
        $response->assertSee('value="2000-01-01"', false);
        $response->assertSee('編集前の説明');
        $response->assertSee('value="https://example.com/before.jpg"', false);

        // 紐づいているジャンルのチェックボックスだけが checked になっている
        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/value="'.$selectedGenre->id.'"\s+class="[^"]*"\s+checked/', $content);
        $this->assertDoesNotMatchRegularExpression('/value="'.$otherGenre->id.'"\s+class="[^"]*"\s+checked/', $content);
    }

    /**
     * 3-4-4 各項目の入力内容が不正な場合、書籍登録機能と同様のバリデーションメッセージが表示される
     */
    public function test_validation_errors_are_shown_same_as_store(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($owner)->create(['title' => '変更されないタイトル']);
        Book::factory()->for(User::factory())->create(['isbn' => '9784000000005']);

        $cases = [
            ['title', '', '書籍タイトルを入力してください'],
            ['title', str_repeat('あ', 256), '書籍タイトルは255文字以内で入力してください'],
            ['author', '', '著者名を入力してください'],
            ['isbn', '12345', 'ISBNは13桁の数字で入力してください'],
            ['isbn', '9784000000005', 'このISBNはすでに登録されています'],
            ['published_date', 'not-a-date', '正しい出版日を入力してください'],
            ['description', str_repeat('あ', 1001), '説明は1000文字以内で入力してください'],
            ['image_url', 'not-a-url', 'URL形式で入力してください'],
            ['genres', [], 'ジャンルを1つ以上選択してください'],
        ];

        foreach ($cases as [$field, $value, $message]) {
            $response = $this->actingAs($owner)->put(
                route('books.update', $book),
                $this->validPayload($genre, [$field => $value])
            );

            $response->assertSessionHasErrors([$field => $message]);
        }

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '変更されないタイトル']);
    }

    /**
     * 3-4-5 ISBNの重複チェックにおいて、自分自身のデータは対象外となる
     */
    public function test_unique_check_excludes_the_book_itself(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($owner)->create([
            'title' => '変わらないタイトル',
            'isbn' => '9784000000002',
        ]);
        $book->genres()->attach($genre);

        $response = $this->actingAs($owner)->put(route('books.update', $book), $this->validPayload($genre, [
            'title' => '変わらないタイトル',
            'isbn' => '9784000000002',
        ]));

        $response->assertSessionDoesntHaveErrors(['title', 'isbn']);
        $response->assertRedirect(route('books.show', $book));
        $this->assertDatabaseHas('books', ['id' => $book->id, 'author' => '更新後の著者', 'isbn' => '9784000000002']);
    }

    /**
     * 3-4-6 全ての項目が正しく入力されている場合、書籍情報が更新される
     */
    public function test_book_is_updated_with_valid_data(): void
    {
        $owner = User::factory()->create();
        $beforeGenre = Genre::factory()->create();
        $afterGenre = Genre::factory()->create();
        $book = Book::factory()->for($owner)->create(['title' => '更新前']);
        $book->genres()->attach($beforeGenre);

        $response = $this->actingAs($owner)->put(route('books.update', $book), $this->validPayload($afterGenre, ['title' => '更新後']));

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', '「更新後」の情報を更新しました。');
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後',
            'author' => '更新後の著者',
            'isbn' => '9784000000009',
            'description' => '更新後の説明',
            'image_url' => 'https://example.com/updated.jpg',
        ]);
        $this->assertDatabaseHas('book_genre', ['book_id' => $book->id, 'genre_id' => $afterGenre->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id, 'genre_id' => $beforeGenre->id]);

        // 遷移先の書籍詳細画面にメッセージが表示される
        $this->get(route('books.show', $book))->assertSee('「更新後」の情報を更新しました。');
    }
}
