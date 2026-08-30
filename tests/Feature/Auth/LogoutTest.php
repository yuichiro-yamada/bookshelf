<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ログアウト機能のテスト（テストケース一覧「ログアウト機能」に対応）
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logged_in_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
