<?php

namespace Tests\Feature\PublicApi;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ユーザー情報取得APIのテスト（テストケース一覧 10-1「ユーザー情報取得API(/api/user)」に対応）
 */
class UserApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 10-1-1 未認証でアクセスすると401が返る
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
        $response->assertExactJson(['message' => '認証が必要です']);
    }

    /**
     * 10-1-2 有効なSanctumトークンでアクセスするとログインユーザーの情報が返る
     */
    public function test_valid_token_returns_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'API太郎', 'email' => 'api@example.com']);
        User::factory()->create(); // 別のユーザー
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/user');

        $response->assertOk();
        $response->assertJson([
            'id' => $user->id,
            'name' => 'API太郎',
            'email' => 'api@example.com',
        ]);
    }

    /**
     * 10-1-3 レスポンスにパスワードなど非公開情報が含まれない
     */
    public function test_response_does_not_contain_hidden_attributes(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/user');

        $response->assertOk();
        $response->assertJsonMissingPath('password');
        $response->assertJsonMissingPath('remember_token');
    }
}
