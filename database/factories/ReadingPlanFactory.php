<?php

namespace Database\Factories;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReadingPlan>
 */
class ReadingPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * 既定は「進行中」で、期日は1週間後。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'user_id' => User::factory(),
            'target_date' => now()->addWeek()->toDateString(),
            'completed_at' => null,
            'status' => ReadingPlanStatus::InProgress,
        ];
    }

    /**
     * 「完了」状態の計画
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    /**
     * 「期限切れ」状態の計画（期日は3日前）
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReadingPlanStatus::Expired,
            'target_date' => now()->subDays(3)->toDateString(),
        ]);
    }
}
