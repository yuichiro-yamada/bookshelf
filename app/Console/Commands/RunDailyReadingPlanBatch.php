<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunDailyReadingPlanBatch extends Command
{
    protected $signature = 'reading-plans:daily';
    protected $description = '読書計画の日次バッチ（期限切れの更新 → 3日前・当日・3日後の通知）をまとめて実行する';

    public function handle(): int
    {
        // 1. 期日を過ぎた「進行中」→「期限切れ」へ更新
        //    （通知より先に実行し、ステータスを最新化してから通知対象を抽出する）
        $this->info('[1/2] 期限切れステータスの更新');
        $expired = $this->call('reading-plans:expire');

        // 2. 期日3日前 / 期日当日 / 期日3日後（期限切れ）の通知を送信
        $this->info('[2/2] リマインダー通知の送信');
        $reminded = $this->call('reading-plans:remind');

        return ($expired === self::SUCCESS && $reminded === self::SUCCESS)
            ? self::SUCCESS
            : self::FAILURE;
    }
}
