<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckReadingPlans extends Command
{
    protected $signature = 'reading-plans:check';
    protected $description = '期日超過の読書計画のステータス更新と通知送信';

    public function handle(): void
    {
        $today = Carbon::today();

        // ① 期日超過の「進行中」→「期限切れ」へ
        ReadingPlan::where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', '<', $today)
            ->update(['status' => ReadingPlanStatus::Expired->value]);

        // ② 期日3日前・進行中 → 予告リマインダー
        ReadingPlan::with(['book', 'user'])
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', $today->copy()->addDays(3))
            ->get()
            ->each(fn ($plan) => $plan->user->notify(new ReadingPlanReminder($plan, 'upcoming')));

        // ③ 期日当日・進行中 → 最終警告リマインダー
        ReadingPlan::with(['book', 'user'])
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', $today)
            ->get()
            ->each(fn ($plan) => $plan->user->notify(new ReadingPlanReminder($plan, 'final')));

        // ④ 期日3日後・期限切れ → 再エンゲージメント通知
        ReadingPlan::with(['book', 'user'])
            ->where('status', ReadingPlanStatus::Expired->value)
            ->whereDate('target_date', $today->copy()->subDays(3))
            ->get()
            ->each(fn ($plan) => $plan->user->notify(new ReadingPlanReminder($plan, 're_engagement')));
    }
}
