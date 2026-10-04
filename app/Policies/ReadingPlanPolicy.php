<?php

namespace App\Policies;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    /**
     * 指定した書籍の読書計画を新規作成できるか
     *
     * 同じ書籍について「進行中」の計画がすでにある場合は作成できない。
     * 「完了」「期限切れ」の計画のみがある場合は、再度作成できる。
     */
    public function create(User $user, Book $book): bool
    {
        return ! $book->readingPlans()
            ->where('user_id', $user->id)
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->exists();
    }

    /**
     * 読書計画を更新（期日の変更）できるか
     *
     * 計画の作成者本人のみ可能。ただし状態によって次のように制限する。
     * - 完了: 変更不可
     * - 期限切れ: 更新すると「進行中」に戻るため、同じ書籍に別の「進行中」の計画が
     *   ある場合は不可（進行中の計画が重複しないようにする）
     * - 進行中: 変更可
     */
    public function update(User $user, ReadingPlan $readingPlan): bool
    {
        if ($user->id !== $readingPlan->user_id) {
            return false;
        }

        return match ($readingPlan->status) {
            ReadingPlanStatus::Completed => false,
            ReadingPlanStatus::Expired => $this->create($user, $readingPlan->book),
            ReadingPlanStatus::InProgress => true,
        };
    }

    /**
     * 読書計画を読了済みにできるか
     *
     * 計画の作成者本人のみ可能。すでに「完了」の計画は不可。
     * 「期限切れ」の計画は、読了済みにすることができる。
     */
    public function complete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id
            && $readingPlan->status !== ReadingPlanStatus::Completed;
    }

    /**
     * 読書計画を削除できるか
     */
    public function delete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }
}
