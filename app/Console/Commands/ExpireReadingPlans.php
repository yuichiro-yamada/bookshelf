<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpireReadingPlans extends Command
{
    protected $signature = 'reading-plans:expire';
    protected $description = '期日超過の読書計画のステータスを「期限切れ」に更新する';

    /**
     * 期日を過ぎた「進行中」の読書計画を「期限切れ」に更新する
     */
    public function handle(): void
    {
        $today = Carbon::today();

        // 期日超過の「進行中」→「期限切れ」へ
        ReadingPlan::where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', '<', $today)
            ->update(['status' => ReadingPlanStatus::Expired->value]);
    }
}
