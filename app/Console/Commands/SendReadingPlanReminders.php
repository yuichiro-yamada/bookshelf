<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendReadingPlanReminders extends Command
{
    protected $signature = 'reading-plans:remind';
    protected $description = '読書計画の期日に応じたリマインダー通知を送信する';

    public function handle(): void
    {
        $today = Carbon::today();

        // 期日3日前・進行中 → 予告リマインダー
        ReadingPlan::with(['book', 'user'])
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', $today->copy()->addDays(3))
            ->get()
            ->each(fn ($plan) => $plan->user->notify(new ReadingPlanReminder($plan, 'upcoming')));

        // 期日当日・進行中 → 最終警告リマインダー
        ReadingPlan::with(['book', 'user'])
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', $today)
            ->get()
            ->each(fn ($plan) => $plan->user->notify(new ReadingPlanReminder($plan, 'final')));

        // 期日3日後・期限切れ → 再エンゲージメント通知
        ReadingPlan::with(['book', 'user'])
            ->where('status', ReadingPlanStatus::Expired->value)
            ->whereDate('target_date', $today->copy()->subDays(3))
            ->get()
            ->each(fn ($plan) => $plan->user->notify(new ReadingPlanReminder($plan, 're_engagement')));
    }
}
