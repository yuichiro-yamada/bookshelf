<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenRequest extends FormRequest
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
            'device_name' => ['nullable', 'string', 'max:255'],
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
            'device_name.max' => 'トークン名は255文字以内で入力してください',
        ];
    }

    /**
     * 入力形式のチェックを通過した後、メールアドレスとパスワードが会員情報と一致するかを確認する。
     * 一致した会員を返し、一致しない場合は password フィールドにエラーメッセージを付与して例外を投げる。
     *
     * 画面用の LoginRequest::authenticate() は Auth::attempt()（セッションを使うログイン）を
     * 呼んでいるが、API リクエストにはセッションがないため、ここではログイン状態は作らず
     * 認証情報の照合だけを行う。
     */
    public function authenticate(): User
    {
        $user = User::where('email', $this->input('email'))->first();

        if (! $user || ! Hash::check($this->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => '会員情報が登録されていません',
            ]);
        }

        return $user;
    }
}
