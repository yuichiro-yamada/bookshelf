<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use App\Models\Book;

class ReviewPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Book $book): bool
    {
        return ! $book->reviews()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }
}
