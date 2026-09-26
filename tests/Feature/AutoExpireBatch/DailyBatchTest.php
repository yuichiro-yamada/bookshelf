<?php

namespace Tests\Feature\AutoExpireBatch;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * 日次バッチ(reading-plans:daily)の実行順・スケジュールのテスト
 * （テストケース一覧 20-2「日次バッチ(reading-plans:daily)の実行順・スケジュール」に対応）
 */
class DailyBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-26 20:00:00');
    }

    /**
     * 20-2-1 reading-plans:dailyを実行すると、期限切れへの更新と通知の送信の両方が行われる
     */
    public function test_daily_batch_expires_plans_and_sends_reminders(): void
    {
        Notification::fake();
        $overduePlan = ReadingPlan::factory()->create(['target_date' => '2026-09-20']);
        $upcomingPlan = ReadingPlan::factory()->create(['target_date' => '2026-09-29']);

        $this->artisan('reading-plans:daily')->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::Expired, $overduePlan->fresh()->status);
        Notification::assertSentTo(
            $upcomingPlan->user,
            ReadingPlanReminder::class,
            fn (ReadingPlanReminder $notification) => $notification->toDatabase($upcomingPlan->user)['timing'] === 'three_days_before'
        );
    }

    /**
     * 20-2-2 期限切れへの更新が、通知対象の抽出より先に行われる
     *
     * 期日の3日後で「進行中」のまま残っている計画は、先に「期限切れ」へ更新されることで、
     * 同じ実行の中で再エンゲージメント通知（期限切れ・期日3日後）の対象になる。
     */
    public function test_expiration_runs_before_reminder_extraction(): void
    {
        Notification::fake();
        $plan = ReadingPlan::factory()->create([
            'target_date' => '2026-09-23',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:daily')->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::Expired, $plan->fresh()->status);
        Notification::assertSentTo(
            $plan->user,
            ReadingPlanReminder::class,
            fn (ReadingPlanReminder $notification) => $notification->toDatabase($plan->user)['timing'] === 'three_days_after'
        );
    }

    /**
     * 20-2-3 reading-plans:dailyが毎日20:00に実行されるようスケジュールされている
     */
    public function test_daily_batch_is_scheduled_at_20_00_every_day(): void
    {
        $schedule = $this->app->make(Schedule::class);

        $events = collect($schedule->events())
            ->filter(fn (Event $event) => str_contains((string) $event->command, 'reading-plans:daily'));

        $this->assertCount(1, $events, 'reading-plans:daily がスケジュールに登録されていません');
        $this->assertSame('0 20 * * *', $events->first()->expression);
    }
}
