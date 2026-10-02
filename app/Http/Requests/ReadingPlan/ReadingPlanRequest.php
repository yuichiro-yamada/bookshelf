<?php

namespace App\Http\Requests\ReadingPlan;

use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Foundation\Http\FormRequest;

class ReadingPlanRequest extends FormRequest
{
    /**
     * このリクエストを実行してよいか
     *
     * 入力チェック（rules）より先に権限を判定するため、ここで ReadingPlanPolicy を呼び出す。
     * 権限がない場合は、入力内容にかかわらず 403 になる。
     * - 更新（route に {readingPlan} が含まれる場合）: 計画の作成者本人かつ更新可能な状態であること（ReadingPlanPolicy::update）
     * - 新規作成: 選択した書籍に、自分の「進行中」の計画がないこと（ReadingPlanPolicy::create）
     *   書籍が特定できない場合（未選択・存在しないID など）は判定対象がないため許可し、
     *   入力チェック（book_id の required・exists）でエラーにする。
     */
    public function authorize(): bool
    {
        $readingPlan = $this->route('readingPlan');

        if ($readingPlan instanceof ReadingPlan) {
            return $this->user()?->can('update', $readingPlan) ?? false;
        }

        $bookId = $this->input('book_id');
        $book = is_scalar($bookId) ? Book::find($bookId) : null;

        if ($book === null) {
            return true;
        }

        return $this->user()?->can('create', [ReadingPlan::class, $book]) ?? false;
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
