<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // 読書計画の日次バッチ（毎日20時）
        //   1. 期日を過ぎた進行中の計画を「期限超過」に更新
        //   2. 期日3日前（進行中）の予告リマインダー通知
        //   3. 期日当日（進行中）の最終警告リマインダー通知
        //   4. 期日3日後（期限超過）の再エンゲージメント通知
        $schedule->command('reading-plans:daily')->dailyAt('20:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
