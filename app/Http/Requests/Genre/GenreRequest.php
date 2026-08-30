<?php

namespace App\Http\Requests\Genre;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenreRequest extends FormRequest
{
    /**
     * このリクエストを実行してよいか
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     *
     * 編集画面（route に {genre} が含まれる場合）では、自分自身の
     * name は重複チェックの対象から除外する。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $genre = $this->route('genre');

        return [
            'name' => [
                'required',
                'string',
                'max:20',
                Rule::unique('genres', 'name')->ignore($genre),
            ],
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
            'name.required' => 'ジャンル名を入力してください',
            'name.max' => 'ジャンル名は20文字以内で入力してください',
            'name.unique' => 'このジャンル名はすでに登録されています',
        ];
    }
}
