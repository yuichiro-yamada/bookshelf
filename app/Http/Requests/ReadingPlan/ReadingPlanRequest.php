<?php

namespace App\Http\Requests\ReadingPlan;

use Illuminate\Foundation\Http\FormRequest;

class ReadingPlanRequest extends FormRequest
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
     * 編集画面（route に {readingPlan} が含まれる場合）は期日のみ変更できる
     * ため、book_id のチェックは新規作成時のみ行う。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isUpdate = $this->route('readingPlan') !== null;

        $rules = [
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ];

        if (! $isUpdate) {
            $rules['book_id'] = ['required', 'integer', 'exists:books,id'];
        }

        return $rules;
    }

    /**
     * エラーメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を選択してください',
            'book_id.integer' => '書籍の指定が正しくありません',
            'book_id.exists' => '選択された書籍が見つかりません',
            'target_date.required' => '期日を入力してください',
            'target_date.date' => '正しい日付を入力してください',
            'target_date.after_or_equal' => '期日には今日以降の日付を指定してください',
        ];
    }
}
