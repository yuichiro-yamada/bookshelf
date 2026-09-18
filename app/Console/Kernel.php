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
        // 失効判定を先に行ってから、当日分の通知判定を行う(元の実行順を踏襲)
        $schedule->command('reading-plans:expire')->dailyAt('22:00');
        $schedule->command('reading-plans:remind')->dailyAt('22:00');
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
