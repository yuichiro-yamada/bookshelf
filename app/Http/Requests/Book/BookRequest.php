<?php

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
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
     * 編集画面（route に {book} が含まれる場合）では、自分自身の
     * title・isbn は重複チェックの対象から除外する。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $book = $this->route('book');

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('books', 'title')->ignore($book),
            ],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => [
                'required',
                'digits:13',
                Rule::unique('books', 'isbn')->ignore($book),
            ],
            'published_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['nullable', 'url'],
            'genres' => ['required', 'array', 'min:1'],
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
            'title.required' => '書籍タイトルを入力してください',
            'title.max' => '書籍タイトルは255文字以内で入力してください',
            'title.unique' => 'この書籍タイトルはすでに登録されています',
            'author.required' => '著者名を入力してください',
            'author.max' => '著者名は255文字以内で入力してください',
            'isbn.required' => 'ISBNを入力してください',
            'isbn.digits' => 'ISBNは13桁の数字で入力してください',
            'isbn.unique' => 'このISBNはすでに登録されています',
            'published_date.required' => '出版日を入力してください',
            'published_date.date' => '正しい出版日を入力してください',
            'description.max' => '説明は1000文字以内で入力してください',
            'image_url.url' => 'URL形式で入力してください',
            'genres.required' => 'ジャンルを1つ以上選択してください',
            'genres.min' => 'ジャンルを1つ以上選択してください',
        ];
    }
}
