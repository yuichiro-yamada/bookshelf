<?php

namespace Tests\Unit\Models;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ReadingPlanモデルの単体テスト（テストケース一覧 1-4「ReadingPlanモデル」に対応）
 */
class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1-4-1 book・userとの関連が取得できる
     */
    public function test_book_and_user_relations(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->for($book)->create();

        $this->assertTrue($plan->book->is($book));
        $this->assertTrue($plan->user->is($user));
    }

    /**
     * 1-4-2 status属性がReadingPlanStatus Enumにキャストされる
     */
    public function test_status_is_cast_to_reading_plan_status_enum(): void
    {
        $plan = ReadingPlan::factory()->create(['status' => 'in_progress']);

        $fresh = $plan->fresh();

        $this->assertInstanceOf(ReadingPlanStatus::class, $fresh->status);
        $this->assertSame(ReadingPlanStatus::InProgress, $fresh->status);
        $this->assertDatabaseHas('reading_plans', ['id' => $plan->id, 'status' => 'in_progress']);
    }
}
