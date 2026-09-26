<?php

namespace Tests\Feature\ReminderBatch;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * 日次バッチ(reading-plans:daily)によるリマインダー通知のテスト
 * （テストケース一覧 19-1「日次バッチ(reading-plans:daily)によるリマインダー通知」に対応）
 */
class ReminderBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 日付の境界（0時前後）で結果が変わらないよう、実行日時を固定する（バッチの実行時刻と同じ20:00）
        Carbon::setTestNow('2026-09-26 20:00:00');
        Notification::fake();
    }

    /**
     * 指定した期日・状態の読書計画を作成する
     */
    private function createPlan(string $targetDate, string $state = 'inProgress', string $bookTitle = 'テスト書籍'): ReadingPlan
    {
        $factory = ReadingPlan::factory()->for(Book::factory()->create(['title' => $bookTitle]));

        $factory = match ($state) {
            'completed' => $factory->completed(),
            'expired' => $factory->expired(),
            default => $factory,
        };

        return $factory->create(['target_date' => $targetDate]);
    }

    /**
     * 送信された通知の data（notifications.data に保存される内容）が期待どおりか確認する
     */
    private function assertReminderSent(ReadingPlan $plan, array $expectedData): void
    {
        Notification::assertSentTo(
            $plan->user,
            ReadingPlanReminder::class,
            fn (ReadingPlanReminder $notification, array $channels) => $channels === ['database']
                && $notification->toDatabase($plan->user) === $expectedData
        );
        Notification::assertSentToTimes($plan->user, ReadingPlanReminder::class, 1);
    }

    /**
     * 19-1-1 期日の3日前かつ進行中の計画に予告リマインダー通知が送信される
     */
    public function test_upcoming_reminder_is_sent_three_days_before(): void
    {
        $plan = $this->createPlan('2026-09-29', 'inProgress', '三日前の書籍');

        $this->artisan('reading-plans:daily')->assertSuccessful();

        $this->assertReminderSent($plan, [
            'plan_id' => $plan->id,
            'book_title' => '三日前の書籍',
            'timing' => 'three_days_before',
            'title' => '読書期限のお知らせ',
            'body' => '「三日前の書籍」の読書期限まであと3日です。',
        ]);
    }

    /**
     * 19-1-2 期日当日かつ進行中の計画に最終警告リマインダー通知が送信される
     */
    public function test_final_reminder_is_sent_on_due_date(): void
    {
        $plan = $this->createPlan('2026-09-26', 'inProgress', '当日の書籍');

        $this->artisan('reading-plans:daily')->assertSuccessful();

        $this->assertReminderSent($plan, [
            'plan_id' => $plan->id,
            'book_title' => '当日の書籍',
            'timing' => 'on_due_date',
            'title' => '読書期限当日のお知らせ',
            'body' => '「当日の書籍」の読書期限は本日までです。',
        ]);
    }

    /**
     * 19-1-3 期限切れから3日後の計画に再エンゲージメント通知が送信される
     */
    public function test_re_engagement_notification_is_sent_three_days_after(): void
    {
        $plan = $this->createPlan('2026-09-23', 'expired', '三日後の書籍');

        $this->artisan('reading-plans:daily')->assertSuccessful();

        $this->assertReminderSent($plan, [
            'plan_id' => $plan->id,
            'book_title' => '三日後の書籍',
            'timing' => 'three_days_after',
            'title' => '読書計画見直しのお知らせ',
            'body' => '「三日後の書籍」の読書期限を過ぎています。計画を見直しませんか？',
        ]);
    }

    /**
     * 19-1-4 いずれの条件にも該当しない計画には通知が送信されない
     */
    public function test_no_notification_for_plans_not_matching_conditions(): void
    {
        $oneWeekLater = $this->createPlan('2026-10-03');
        $twoDaysLater = $this->createPlan('2026-09-28');
        $oneDayAgoExpired = $this->createPlan('2026-09-25', 'expired');

        $this->artisan('reading-plans:daily')->assertSuccessful();

        Notification::assertNotSentTo($oneWeekLater->user, ReadingPlanReminder::class);
        Notification::assertNotSentTo($twoDaysLater->user, ReadingPlanReminder::class);
        Notification::assertNotSentTo($oneDayAgoExpired->user, ReadingPlanReminder::class);
        Notification::assertNothingSent();
    }

    /**
     * 19-1-5 ステータスが「完了」の計画には、期日の条件に合っていても通知が送信されない
     */
    public function test_no_notification_for_completed_plans(): void
    {
        $this->createPlan('2026-09-29', 'completed'); // 3日後
        $this->createPlan('2026-09-26', 'completed'); // 当日
        $this->createPlan('2026-09-23', 'completed'); // 3日前

        $this->artisan('reading-plans:daily')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
