<?php

namespace Tests\Feature\SanctumAuth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sanctumによるトークン認証のテスト（テストケース一覧 16-1「APIトークン認証」に対応）
 *
 * トークン発行用のエンドポイントは設けていないため、$user->createToken() で直接発行する。
 */
class SanctumAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 16-1-1 有効なpersonal access tokenを付与すると/api/userにアクセスできる
     */
    public function test_valid_token_can_access_user_endpoint(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/user');

        $response->assertOk();
        $response->assertJsonPath('id', $user->id);
        $response->assertJsonPath('email', $user->email);
    }

    /**
     * 16-1-2 トークンなしでアクセスすると401が返る
     */
    public function test_request_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
        $response->assertExactJson(['message' => '認証が必要です']);
    }

    /**
     * 16-1-3 不正な(存在しない)トークンでアクセスすると401が返る
     */
    public function test_invalid_token_returns_401(): void
    {
        User::factory()->create()->createToken('test');

        $response = $this->withToken('1|thisIsNotAValidTokenString1234567890')->getJson('/api/user');

        $response->assertUnauthorized();
        $response->assertExactJson(['message' => '認証が必要です']);
    }

    /**
     * 16-1-4 削除済み(失効済み)のトークンでアクセスすると401が返る
     */
    public function test_revoked_token_returns_401(): void
    {
        $user = User::factory()->create();
        $newToken = $user->createToken('test');
        $plainTextToken = $newToken->plainTextToken;

        // トークンを削除（失効）する
        $newToken->accessToken->delete();
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $response = $this->withToken($plainTextToken)->getJson('/api/user');

        $response->assertUnauthorized();
        $response->assertExactJson(['message' => '認証が必要です']);
    }
}
