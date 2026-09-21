<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendReadingPlanReminders extends Command
{
    protected $signature = 'reading-plans:remind';
    protected $description = '読書計画の期日に応じたリマインダー通知を送信する';

    /**
     * 期日に応じたリマインダー通知を送信する
     *
     * - 期日3日前・進行中: 予告リマインダー
     * - 期日当日・進行中: 最終警告リマインダー
     * - 期日3日後・期限切れ: 再エンゲージメント通知
     */
    public function handle(): void
    {
        $today = Carbon::today();

        $this->sendReminders(ReadingPlanStatus::InProgress, $today->copy()->addDays(3), 'upcoming');
        $this->sendReminders(ReadingPlanStatus::InProgress, $today, 'final');
        $this->sendReminders(ReadingPlanStatus::Expired, $today->copy()->subDays(3), 're_engagement');
    }

    /**
     * 指定したステータスで、期日が指定日の読書計画の所有者に通知を送る
     *
     * @param  ReadingPlanStatus  $status  対象のステータス
     * @param  Carbon  $targetDate  対象の期日
     * @param  string  $type  通知の種類（ReadingPlanReminder の type）
     */
    private function sendReminders(ReadingPlanStatus $status, Carbon $targetDate, string $type): void
    {
        ReadingPlan::with(['book', 'user'])
            ->where('status', $status->value)
            ->whereDate('target_date', $targetDate)
            ->get()
            ->each(fn (ReadingPlan $plan) => Notification::send($plan->user, new ReadingPlanReminder($plan, $type)));
    }
}
