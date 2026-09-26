<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 会員登録機能のテスト（テストケース一覧「会員登録」に対応）
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * バリデーションを通過するための正しい入力値
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    /**
     * 9-1-1 名前が入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_name_is_required(): void
    {
        $response = $this->post(route('register'), $this->validPayload(['name' => '']));

        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
        $this->assertGuest();
    }

    /**
     * 9-1-2 名前が21文字以上の場合、バリデーションメッセージが表示される
     */
    public function test_name_must_not_exceed_20_characters(): void
    {
        $response = $this->post(route('register'), $this->validPayload(['name' => str_repeat('あ', 21)]));

        $response->assertSessionHasErrors(['name' => 'お名前は20文字以内で入力してください']);
    }

    /**
     * 9-1-3 メールアドレスが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_email_is_required(): void
    {
        $response = $this->post(route('register'), $this->validPayload(['email' => '']));

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /**
     * 9-1-4 メールアドレスがメール形式でない場合、バリデーションメッセージが表示される
     */
    public function test_email_must_be_valid_format(): void
    {
        $response = $this->post(route('register'), $this->validPayload(['email' => 'invalid-email']));

        $response->assertSessionHasErrors(['email' => 'メールアドレスはメール形式で入力してください']);
    }

    /**
     * 9-1-5 メールアドレスが256文字以上の場合、バリデーションメッセージが表示される
     */
    public function test_email_must_not_exceed_255_characters(): void
    {
        // ローカル部64文字（RFC上の上限）+ ドメイン部で255文字を超える、形式としては正しいメールアドレス
        $localPart = str_repeat('a', 64);
        $domainLabel = str_repeat('b', 60);
        $domain = implode('.', array_fill(0, 4, $domainLabel)).'.com';
        $longEmail = $localPart.'@'.$domain;

        $response = $this->post(route('register'), $this->validPayload(['email' => $longEmail]));

        $response->assertSessionHasErrors(['email' => 'メールアドレスは255文字以内で入力してください']);
    }

    /**
     * 9-1-6 既に登録されているメールアドレスの場合、バリデーションメッセージが表示される
     */
    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'exists@example.com']);

        $response = $this->post(route('register'), $this->validPayload(['email' => 'exists@example.com']));

        $response->assertSessionHasErrors(['email' => 'このメールアドレスはすでに登録されています']);
    }

    /**
     * 9-1-7 パスワードが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_password_is_required(): void
    {
        $response = $this->post(route('register'), $this->validPayload(['password' => '', 'password_confirmation' => '']));

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /**
     * 9-1-8 パスワードが7文字以下の場合、バリデーションメッセージが表示される
     */
    public function test_password_must_be_at_least_8_characters(): void
    {
        $response = $this->post(route('register'), $this->validPayload([
            'password' => 'pass12',
            'password_confirmation' => 'pass12',
        ]));

        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    /**
     * 9-1-9 パスワードが21文字以上の場合、バリデーションメッセージが表示される
     */
    public function test_password_must_not_exceed_20_characters(): void
    {
        $longPassword = str_repeat('a', 21);

        $response = $this->post(route('register'), $this->validPayload([
            'password' => $longPassword,
            'password_confirmation' => $longPassword,
        ]));

        $response->assertSessionHasErrors(['password' => 'パスワードは20文字以内で入力してください']);
    }

    /**
     * 9-1-10 確認用パスワードが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_password_confirmation_is_required(): void
    {
        $response = $this->post(route('register'), $this->validPayload(['password_confirmation' => '']));

        $response->assertSessionHasErrors(['password_confirmation' => '確認用パスワードを入力してください']);
    }

    /**
     * 9-1-11 パスワードが確認用パスワードと一致しない場合、バリデーションメッセージが表示される
     */
    public function test_password_confirmation_must_match_password(): void
    {
        $response = $this->post(route('register'), $this->validPayload(['password_confirmation' => 'different123']));

        $response->assertSessionHasErrors(['password_confirmation' => 'パスワードが一致しません']);
    }

    /**
     * 9-1-12 全ての項目が正しく入力されている場合、会員登録が完了する
     */
    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->post(route('register'), $this->validPayload());

        $response->assertRedirect(route('books.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
        ]);
    }
}
