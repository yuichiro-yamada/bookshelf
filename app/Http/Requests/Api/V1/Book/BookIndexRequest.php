<?php

namespace App\Http\Requests\Api\V1\Book;

use Illuminate\Foundation\Http\FormRequest;

class BookIndexRequest extends FormRequest
{
    /**
     * このリクエストを実行してよいか
     *
     * 一覧取得は誰でも閲覧できるため常に許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'genre' => ['nullable', 'integer', 'exists:genres,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
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
            'keyword.string' => 'キーワードは文字列で指定してください',
            'keyword.max' => 'キーワードは255文字以内で指定してください',

            'genre.integer' => 'ジャンルIDは整数で指定してください',
            'genre.exists' => '指定されたジャンルは存在しません',

            'page.integer' => 'ページ番号は整数で指定してください',
            'page.min' => 'ページ番号は1以上の値で指定してください',

            'per_page.integer' => '取得件数は整数で指定してください',
            'per_page.min' => '取得件数は1以上の値で指定してください',
            'per_page.max' => '取得件数は100以下の値で指定してください',
        ];
    }
}
