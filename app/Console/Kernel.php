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
        // 期日を過ぎた進行中の計画を「期限超過」に更新する（日付が変わった直後に実行）
        $schedule->command('reading-plans:expire')->dailyAt('00:00');

        // 期日に応じたリマインダー通知を送信する（要件: 20時）
        $schedule->command('reading-plans:remind')->dailyAt('20:00');
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
