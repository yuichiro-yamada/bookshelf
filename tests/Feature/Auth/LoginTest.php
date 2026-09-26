<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ログイン機能のテスト（テストケース一覧「ログイン機能」に対応）
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 9-2-1 メールアドレスが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_email_is_required(): void
    {
        $response = $this->post(route('login'), [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
        $this->assertGuest();
    }

    /**
     * 9-2-2 メールアドレスがメール形式でない場合、バリデーションメッセージが表示される
     */
    public function test_email_must_be_valid_format(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスはメール形式で入力してください']);
    }

    /**
     * 9-2-3 パスワードが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_password_is_required(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /**
     * 9-2-4 入力情報が登録されている会員情報と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_login_fails_when_credentials_do_not_match(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['password' => '会員情報が登録されていません']);
        $this->assertGuest();

        // 登録されていないメールアドレスの場合も同じメッセージになる
        $response = $this->post(route('login'), [
            'email' => 'unknown@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['password' => '会員情報が登録されていません']);
        $this->assertGuest();
    }

    /**
     * 9-2-5 正しい情報が入力された場合、ログイン処理が実行される
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('books.index'));
        $this->assertAuthenticatedAs($user);
    }
}
