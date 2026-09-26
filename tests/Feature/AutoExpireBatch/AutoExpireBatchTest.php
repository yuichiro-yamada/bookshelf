<?php

namespace Tests\Feature\AutoExpireBatch;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * 日次バッチ(reading-plans:daily)による自動失効処理のテスト
 * （テストケース一覧 20-1「日次バッチ(reading-plans:daily)による自動失効処理」に対応）
 */
class AutoExpireBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-26 20:00:00');
        // このテストでは通知の送信内容は検証しないため、実際の送信は行わない
        Notification::fake();
    }

    /**
     * 20-1-1 期日を過ぎた「進行中」の計画が「期限切れ」に更新される
     */
    public function test_overdue_in_progress_plan_is_expired(): void
    {
        $plan = ReadingPlan::factory()->create(['target_date' => '2026-09-25']);

        $this->artisan('reading-plans:daily')->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::Expired, $plan->fresh()->status);
    }

    /**
     * 20-1-2 期日が今日以降の「進行中」の計画は失効しない
     */
    public function test_in_progress_plans_due_today_or_later_are_not_expired(): void
    {
        $dueToday = ReadingPlan::factory()->create(['target_date' => '2026-09-26']);
        $dueTomorrow = ReadingPlan::factory()->create(['target_date' => '2026-09-27']);

        $this->artisan('reading-plans:daily')->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::InProgress, $dueToday->fresh()->status);
        $this->assertSame(ReadingPlanStatus::InProgress, $dueTomorrow->fresh()->status);
    }

    /**
     * 20-1-3 既に「完了」または「期限切れ」の計画は対象外(重複更新されない)
     */
    public function test_completed_and_expired_plans_are_not_changed(): void
    {
        $completed = ReadingPlan::factory()->completed()->create(['target_date' => '2026-09-20']);
        $expired = ReadingPlan::factory()->expired()->create(['target_date' => '2026-09-20']);
        $completedUpdatedAt = $completed->fresh()->updated_at;
        $expiredUpdatedAt = $expired->fresh()->updated_at;

        Carbon::setTestNow('2026-09-26 20:05:00');
        $this->artisan('reading-plans:daily')->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::Completed, $completed->fresh()->status);
        $this->assertSame(ReadingPlanStatus::Expired, $expired->fresh()->status);
        // 更新処理の対象になっていない（updated_at も変わっていない）
        $this->assertEquals($completedUpdatedAt, $completed->fresh()->updated_at);
        $this->assertEquals($expiredUpdatedAt, $expired->fresh()->updated_at);
    }

    /**
     * 20-1-4 コマンド実行後、対象データのステータスがDBに正しく保存されている
     */
    public function test_expired_status_is_persisted_in_database(): void
    {
        $plan = ReadingPlan::factory()->create(['target_date' => '2026-09-20']);

        $this->artisan('reading-plans:daily')->assertSuccessful();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => 'expired',
        ]);
    }
}
