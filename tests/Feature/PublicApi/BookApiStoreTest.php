<?php

namespace Tests\Feature\PublicApi;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍登録APIのテスト（テストケース一覧 10-4「書籍登録API(POST /api/v1/books)」に対応）
 */
class BookApiStoreTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/v1/books';

    /**
     * バリデーションを通過するための正しい入力値
     */
    private function validPayload(array $overrides = []): array
    {
        $genre = Genre::factory()->create();

        return array_merge([
            'title' => 'API登録書籍',
            'author' => 'API著者',
            'isbn' => '9784000000000',
            'published_date' => '2020-05-01',
            'description' => 'APIから登録した書籍です。',
            'image_url' => 'https://example.com/api.jpg',
            'genres' => [$genre->id],
        ], $overrides);
    }

    /**
     * ユーザーのSanctumトークン（平文）を発行する
     */
    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    /**
     * 10-4-1 認証なしでアクセスすると401が返る
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->postJson(self::URI, $this->validPayload());

        $response->assertUnauthorized();
        $response->assertExactJson(['message' => '認証が必要です']);
        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 10-4-2 有効なトークンで書籍を登録できる
     */
    public function test_book_can_be_created_with_valid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->withToken($this->tokenFor($user))->postJson(self::URI, $this->validPayload());

        $response->assertCreated();
        $response->assertJsonPath('data.title', 'API登録書籍');
        $response->assertJsonPath('data.author', 'API著者');
        $response->assertJsonPath('data.isbn', '9784000000000');
        $response->assertJsonPath('data.published_date', '2020-05-01');
        $response->assertJsonPath('data.description', 'APIから登録した書籍です。');
        $response->assertJsonPath('data.image_url', 'https://example.com/api.jpg');
        $this->assertDatabaseHas('books', [
            'id' => $response->json('data.id'),
            'title' => 'API登録書籍',
            'author' => 'API著者',
            'isbn' => '9784000000000',
        ]);
    }

    /**
     * 10-4-3 登録者(user_id)は、リクエストの値ではなく認証ユーザーになる
     */
    public function test_user_id_is_set_to_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->withToken($this->tokenFor($user))
            ->postJson(self::URI, $this->validPayload(['user_id' => $otherUser->id]));

        $response->assertCreated();
        $response->assertJsonPath('data.user_id', $user->id);
        $this->assertDatabaseHas('books', ['id' => $response->json('data.id'), 'user_id' => $user->id]);
    }

    /**
     * 10-4-4 選択したジャンルが書籍に紐づけられる
     */
    public function test_selected_genres_are_attached(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $response = $this->withToken($this->tokenFor($user))
            ->postJson(self::URI, $this->validPayload(['genres' => $genres->pluck('id')->all()]));

        $response->assertCreated();
        $bookId = $response->json('data.id');
        foreach ($genres as $genre) {
            $this->assertDatabaseHas('book_genre', ['book_id' => $bookId, 'genre_id' => $genre->id]);
        }
        $this->assertEqualsCanonicalizing(
            $genres->pluck('id')->all(),
            array_column($response->json('data.genres'), 'id')
        );
    }

    /**
     * 10-4-5 必須項目(title・author・genres)が未入力の場合、422が返る
     */
    public function test_required_fields_missing_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->withToken($this->tokenFor($user))->postJson(self::URI, [
            'isbn' => '9784000000000',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'title' => '書籍タイトルを入力してください',
            'author' => '著者名を入力してください',
            'genres' => 'ジャンルを1つ以上選択してください',
        ]);
        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 10-4-6 ISBNが13桁の数字でない、または登録済みの場合、422が返る
     */
    public function test_invalid_or_duplicate_isbn_returns_422(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withToken($token)
            ->postJson(self::URI, $this->validPayload(['isbn' => '12345']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['isbn' => 'ISBNは13桁の数字で入力してください']);

        Book::factory()->create(['isbn' => '9784000000001']);

        $this->withToken($token)
            ->postJson(self::URI, $this->validPayload(['isbn' => '9784000000001']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['isbn' => 'このISBNはすでに登録されています']);

        $this->assertDatabaseCount('books', 1);
    }

    /**
     * 10-4-7 存在しないジャンルIDを指定すると422が返る
     */
    public function test_non_existent_genre_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->withToken($this->tokenFor($user))
            ->postJson(self::URI, $this->validPayload(['genres' => [999]]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['genres.0' => '選択されたジャンルは存在しません']);
        $this->assertDatabaseCount('books', 0);
    }
}
