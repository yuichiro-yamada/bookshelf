<?php

namespace App\Http\Requests\Book;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
{
    /**
     * このリクエストを実行してよいか
     *
     * 入力チェック（rules）より先に権限を判定するため、ここで BookPolicy を呼び出す。
     * 権限がない場合は、入力内容にかかわらず 403 になる。
     * - 更新（route に {book} が含まれる場合）: 書籍の登録者本人のみ（BookPolicy::update）
     * - 新規登録: ログインユーザーであれば可（BookPolicy::create）
     */
    public function authorize(): bool
    {
        $book = $this->route('book');

        if ($book instanceof Book) {
            return $this->user()?->can('update', $book) ?? false;
        }

        return $this->user()?->can('create', Book::class) ?? false;
    }

    /**
     * バリデーションルール
     *
     * title は重複可能。isbn・出版日は任意項目。
     * 編集画面（route に {book} が含まれる場合）では、自分自身の
     * isbn は重複チェックの対象から除外する。
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
            ],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => [
                'nullable',
                'digits:13',
                Rule::unique('books', 'isbn')->ignore($book),
            ],
            'published_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'genres' => [
                'required',
                'array',
            ],
            'genres.*' => [
                'integer',
                'exists:genres,id'
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
            'title.required' => '書籍タイトルを入力してください',
            'title.max' => '書籍タイトルは255文字以内で入力してください',

            'author.required' => '著者名を入力してください',
            'author.max' => '著者名は255文字以内で入力してください',

            'isbn.digits' => 'ISBNは13桁の数字で入力してください',
            'isbn.unique' => 'このISBNはすでに登録されています',

            'published_date.date' => '正しい出版日を入力してください',

            'description.max' => '説明は1000文字以内で入力してください',

            'image_url.url' => 'URL形式で入力してください',
            'image_url.max' => '画像URLは255文字以内で入力してください',

            'genres.required' => 'ジャンルを1つ以上選択してください',
            'genres.array' => 'ジャンルの選択肢が不正です',

            'genres.*.integer' => '選択されたジャンルが正しくありません',
            'genres.*.exists' => '選択されたジャンルは存在しません',
        ];
    }
}
