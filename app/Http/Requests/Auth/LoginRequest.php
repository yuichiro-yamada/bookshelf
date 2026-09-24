<?php

namespace App\Http\Requests\Auth;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

/**
 * Fortifyのログイン処理で使われるFormRequest。
 * 入力形式チェック（rules）とエラーメッセージ（messages）を定義する。
 *
 * FortifyServiceProvider の register() 内で
 * $this->app->singleton(\Laravel\Fortify\Http\Requests\LoginRequest::class, self::class)
 * として紐づけることで、Fortify内部のLoginRequestをこのクラスに差し替えている。
 *
 * 実際の認証（email・passwordの組み合わせが正しいか）は
 * FortifyServiceProvider の Fortify::authenticateUsing() 側で行う。
 */
class LoginRequest extends FortifyLoginRequest
{
    /**
     * バリデーションルール（入力の形式チェックのみ）
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * エラーメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスはメール形式で入力してください',
            'password.required' => 'パスワードを入力してください',
        ];
    }
}
