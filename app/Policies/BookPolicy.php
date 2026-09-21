<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * 書籍を登録できるか（ログインユーザーであれば誰でも可）
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * 書籍を更新できるか（登録者本人のみ）
     */
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * 書籍を削除できるか（登録者本人のみ）
     */
    public function delete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }
}
