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
     * 読書計画を更新（読了操作を含む）できるか
     */
    public function update(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }

    /**
     * 読書計画を削除できるか
     */
    public function delete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }
}
