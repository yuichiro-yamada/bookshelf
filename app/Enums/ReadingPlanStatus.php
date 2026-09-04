<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case InProgress = 'in_progress';
    case Overdue = 'overdue';
    case Completed = 'completed';

    /**
     * 画面表示用のラベル
     */
    public function label(): string
    {
        return match ($this) {
            self::InProgress => '進行中',
            self::Overdue => '期限超過',
            self::Completed => '完了',
        };
    }

    /**
     * バッジ表示用のTailwindクラス
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::InProgress => 'bg-blue-100 text-blue-800',
            self::Overdue => 'bg-red-100 text-red-800',
            self::Completed => 'bg-green-100 text-green-800',
        };
    }
}
