<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * このリクエストを実行してよいか
     */
    public function authorize(): bool
    {
        return true;
    }

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

    /**
     * 入力形式のチェックを通過した後、実際にログインできる会員情報かどうかを確認する。
     * 一致しない場合はpasswordフィールドにエラーメッセージを付与して例外を投げる。
     */
    public function authenticate(): void
    {
        if (! Auth::attempt($this->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'password' => '会員情報が登録されていません',
            ]);
        }
    }
}
