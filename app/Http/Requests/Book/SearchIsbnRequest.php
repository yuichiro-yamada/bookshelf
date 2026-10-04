<?php

namespace App\Http\Requests\Book;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchIsbnRequest extends FormRequest
{
    /**
     * このリクエストを実行してよいか
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * ルートパラメータの{isbn}を、バリデーション対象のデータとしてマージする
     *
     * このリクエストはISBNをURLの一部（ルートパラメータ）として受け取るため、
     * 通常のリクエストボディ・クエリパラメータには含まれていない。
     * そのため、バリデーション実行前にルートパラメータを取り込んでおく。
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'isbn' => $this->route('isbn'),
        ]);
    }

    /**
     * バリデーションルール
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'isbn' => ['required', 'digits:13', 'unique:books,isbn'],
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
            'isbn.required' => 'ISBNを入力してください',
            'isbn.digits' => 'ISBNは13桁の数字で入力してください',
            'isbn.unique' => 'このISBNはすでに登録されています',
        ];
    }

    /**
     * バリデーション失敗時のレスポンスを、このアプリのAjax用フォーマットに合わせる
     *
     * デフォルトのFormRequestは {"message": "...", "errors": {...}} という形式を返すが、
     * books/create.blade.php 側のJavaScriptは {"error": "..."} というキーを見て
     * 成功・失敗を判定しているため、それに合わせて上書きする。
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['error' => $validator->errors()->first()], 422)
        );
    }
}
