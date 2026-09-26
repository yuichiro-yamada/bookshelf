<?php

namespace Tests\Feature\PublicApi;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍更新APIのテスト（テストケース一覧 10-5「書籍更新API(PUT /api/v1/books/{book})」に対応）
 */
class BookApiUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * バリデーションを通過するための正しい入力値
     */
    private function validPayload(array $overrides = []): array
    {
        $genre = Genre::factory()->create();

        return array_merge([
            'title' => '更新後タイトル',
            'author' => '更新後の著者',
            'isbn' => '9784000000009',
            'published_date' => '2021-01-01',
            'description' => '更新後の説明',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$genre->id],
        ], $overrides);
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    /**
     * 10-5-1 認証なしでアクセスすると401が返る
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $book = Book::factory()->create(['title' => '更新前タイトル']);

        $response = $this->putJson("/api/v1/books/{$book->id}", $this->validPayload());

        $response->assertUnauthorized();
        $response->assertExactJson(['message' => '認証が必要です']);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '更新前タイトル']);
    }

    /**
     * 10-5-2 自分が登録した書籍を更新できる
     */
    public function test_owner_can_update_book(): void
    {
        $owner = User::factory()->create();
        $beforeGenre = Genre::factory()->create();
        $book = Book::factory()->for($owner)->create(['title' => '更新前タイトル']);
        $book->genres()->attach($beforeGenre);
        $afterGenre = Genre::factory()->create(['name' => '更新後ジャンル']);

        $response = $this->withToken($this->tokenFor($owner))
            ->putJson("/api/v1/books/{$book->id}", $this->validPayload(['genres' => [$afterGenre->id]]));

        $response->assertOk();
        $response->assertJsonPath('data.title', '更新後タイトル');
        $response->assertJsonPath('data.author', '更新後の著者');
        $response->assertJsonPath('data.genres', [['id' => $afterGenre->id, 'name' => '更新後ジャンル']]);
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後の著者',
            'isbn' => '9784000000009',
        ]);
        $this->assertDatabaseHas('book_genre', ['book_id' => $book->id, 'genre_id' => $afterGenre->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id, 'genre_id' => $beforeGenre->id]);
    }

    /**
     * 10-5-3 自分の書籍のISBNを変更せずに更新しても、ISBNの重複エラーにならない
     */
    public function test_updating_without_changing_own_isbn_is_allowed(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create(['isbn' => '9784000000002']);

        $response = $this->withToken($this->tokenFor($owner))
            ->putJson("/api/v1/books/{$book->id}", $this->validPayload(['isbn' => '9784000000002']));

        $response->assertOk();
        $response->assertJsonPath('data.isbn', '9784000000002');
    }

    /**
     * 10-5-4 他のユーザーが登録した書籍は更新できない
     */
    public function test_other_user_cannot_update_book(): void
    {
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['title' => '更新前タイトル']);

        $response = $this->withToken($this->tokenFor($otherUser))
            ->putJson("/api/v1/books/{$book->id}", $this->validPayload());

        $response->assertForbidden();
        $response->assertExactJson(['message' => 'この操作を行う権限がありません']);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '更新前タイトル']);
    }

    /**
     * 10-5-5 存在しないIDを指定すると404が返る
     */
    public function test_non_existent_book_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->withToken($this->tokenFor($user))
            ->putJson('/api/v1/books/999', $this->validPayload());

        $response->assertNotFound();
        $response->assertExactJson(['message' => '指定された書籍が見つかりません']);
    }

    /**
     * 10-5-6 入力内容が不正な場合、422が返る
     */
    public function test_invalid_input_returns_422(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create(['title' => '更新前タイトル']);

        $response = $this->withToken($this->tokenFor($owner))
            ->putJson("/api/v1/books/{$book->id}", $this->validPayload(['title' => '']));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['title' => '書籍タイトルを入力してください']);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '更新前タイトル']);
    }

    /**
     * 10-5-7 他のユーザーが登録した書籍に対して不正な入力で更新しようとしても、422ではなく403が返る
     */
    public function test_authorization_is_checked_before_validation(): void
    {
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['title' => '更新前タイトル']);

        $response = $this->withToken($this->tokenFor($otherUser))
            ->putJson("/api/v1/books/{$book->id}", $this->validPayload(['title' => '']));

        $response->assertForbidden();
        $response->assertExactJson(['message' => 'この操作を行う権限がありません']);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '更新前タイトル']);
    }
}
