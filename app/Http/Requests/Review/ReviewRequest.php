<?php

namespace App\Http\Requests\Review;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    /**
     * このリクエストを実行してよいか
     *
     * 入力チェック（rules）より先に権限を判定するため、ここで ReviewPolicy を呼び出す。
     * 権限がない場合は、入力内容にかかわらず 403 になる。
     * - 更新（route に {review} が含まれる場合）: レビューの投稿者本人のみ（ReviewPolicy::update）
     * - 投稿（route に {book} が含まれる場合）: その書籍にまだレビューを投稿していないこと（ReviewPolicy::create）
     */
    public function authorize(): bool
    {
        $review = $this->route('review');

        if ($review instanceof Review) {
            return $this->user()?->can('update', $review) ?? false;
        }

        $book = $this->route('book');

        return $book instanceof Book
            && ($this->user()?->can('create', [Review::class, $book]) ?? false);
    }

    /**
     * バリデーションルール
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // 星評価（1〜5）を選択させるUIのため、範囲チェックも合わせて入れている
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:255'],
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
            'rating.required' => '評価を入力してください',
            'rating.integer' => '評価は1〜5の整数で入力してください',
            'rating.between' => '評価は1〜5の整数で入力してください',
            'comment.required' => 'コメントを入力してください',
            'comment.max' => 'コメントは255文字以内で入力してください',
        ];
    }
}
