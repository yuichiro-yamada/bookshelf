<?php

namespace Tests\Feature\SanctumAuth;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sanctumによるトークン認証のテスト（テストケース一覧 16-1「AP06:SanctumAPIトークン認証」に対応）
 * ★AP06（応用）の要件「書き込み系エンドポイントへの Sanctum 認証」を確認する。
 *
 * Sanctum認証が必要な書き込み系の公開API（POST/PUT/DELETE /api/v1/books）に対して、
 * トークンの有無・正否による認証結果を確認する。
 * トークン発行用のエンドポイントは設けていないため、$user->createToken() で直接発行する。
 */
class SanctumAuthTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/api/v1/books';

    /**
     * バリデーションを通過するための正しい入力値
     */
    private function validPayload(): array
    {
        return [
            'title' => '認証テストの書籍',
            'author' => '認証テストの著者',
            'genres' => [Genre::factory()->create()->id],
        ];
    }

    /**
     * 書き込み系の3つのエンドポイントがすべて401を返し、データが変更されないことを確認する
     */
    private function assertAllWriteEndpointsReturn401(Book $book): void
    {
        $payload = $this->validPayload();

        $responses = [
            'POST' => $this->postJson(self::URI, $payload),
            'PUT' => $this->putJson(self::URI.'/'.$book->id, $payload),
            'DELETE' => $this->deleteJson(self::URI.'/'.$book->id),
        ];

        foreach ($responses as $response) {
            $response->assertUnauthorized();
            $response->assertExactJson(['message' => '認証が必要です']);
        }

        // 書籍は登録・更新・削除されていない
        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '元のタイトル']);
    }

    /**
     * 16-1-1 有効なpersonal access tokenを付与すると、書き込み系API(POST /api/v1/books)を利用できる
     */
    public function test_valid_token_can_access_write_endpoint(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(self::URI, $this->validPayload());

        $response->assertCreated();
        $this->assertDatabaseHas('books', ['title' => '認証テストの書籍', 'user_id' => $user->id]);
    }

    /**
     * 16-1-2 トークンなしで書き込み系API(POST/PUT/DELETE)にアクセスすると401が返る
     */
    public function test_request_without_token_returns_401(): void
    {
        $book = Book::factory()->create(['title' => '元のタイトル']);

        $this->assertAllWriteEndpointsReturn401($book);
    }

    /**
     * 16-1-3 不正な(存在しない)トークンで書き込み系API(POST/PUT/DELETE)にアクセスすると401が返る
     */
    public function test_invalid_token_returns_401(): void
    {
        $book = Book::factory()->create(['title' => '元のタイトル']);
        User::factory()->create()->createToken('test'); // 有効なトークンが存在する状態にしておく

        $this->withToken('1|thisIsNotAValidTokenString1234567890');

        $this->assertAllWriteEndpointsReturn401($book);
    }

    /**
     * 16-1-4 削除済み(失効済み)のトークンで書き込み系API(POST/PUT/DELETE)にアクセスすると401が返る
     *
     * 書籍の登録者本人のトークンでも、削除（失効）後は更新・削除できない。
     */
    public function test_revoked_token_returns_401(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create(['title' => '元のタイトル']);
        $newToken = $owner->createToken('test');
        $plainTextToken = $newToken->plainTextToken;

        // トークンを削除（失効）する
        $newToken->accessToken->delete();
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->withToken($plainTextToken);

        $this->assertAllWriteEndpointsReturn401($book);
    }
}
