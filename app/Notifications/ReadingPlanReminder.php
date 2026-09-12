<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected ReadingPlan $readingPlan,
        protected string $type // 'upcoming' | 'final' | 're_engagement'
    ){}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlan->id,
            'title' => $this->buildTitle(),
            'body' => $this->buildMessage(),
            'timing' => $this->timingKey(),
        ];
    }

    protected function timingKey(): string
    {
        return match ($this->type) {
            'upcoming' => 'three_days_before',
            'final' => 'on_due_date',
            're_engagement' => 'three_days_after',
        };
    }

    protected function buildTitle(): string
    {
        return match ($this->type) {
            'upcoming' => '読書期限のお知らせ',
            'final' => '読書期限当日のお知らせ',
            're_engagement' => '読書計画見直しのお知らせ',
        };
    }

    protected function buildMessage(): string
    {
        return match ($this->type) {
            'upcoming' => "「{$this->readingPlan->book->title}」の読書期限まであと3日です。",
            'final' => "「{$this->readingPlan->book->title}」の読書期限は本日までです。",
            're_engagement' => "「{$this->readingPlan->book->title}」の読書期限を過ぎています。計画を見直しませんか？",
        };
    }
}
