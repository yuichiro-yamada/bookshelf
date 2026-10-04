<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    use Queueable;

    /**
     * 通知インスタンスを作成する
     *
     * @param  ReadingPlan  $readingPlan  通知の対象となる読書計画
     * @param  string  $type  通知の種類（'upcoming'：3日前 / 'final'：当日 / 're_engagement'：3日後）
     */
    public function __construct(
        protected ReadingPlan $readingPlan,
        protected string $type
    ) {}

    /**
     * 通知の配信チャンネルを返す（データベースのみ）
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * notificationsテーブルのdataカラムに保存する内容を返す
     *
     * @return array<string, int|string>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'plan_id' => $this->readingPlan->id,
            'book_title' => $this->readingPlan->book->title,
            'timing' => $this->timingKey(),
            'title' => $this->buildTitle(),
            'body' => $this->buildMessage(),
        ];
    }

    /**
     * 通知の種類を、保存用のタイミング識別子に変換する
     */
    protected function timingKey(): string
    {
        return match ($this->type) {
            'upcoming' => 'three_days_before',
            'final' => 'on_due_date',
            're_engagement' => 'three_days_after',
        };
    }

    /**
     * 通知の種類に応じたタイトルを返す
     */
    protected function buildTitle(): string
    {
        return match ($this->type) {
            'upcoming' => '読書期限のお知らせ',
            'final' => '読書期限当日のお知らせ',
            're_engagement' => '読書計画見直しのお知らせ',
        };
    }

    /**
     * 通知の種類に応じた本文を返す
     */
    protected function buildMessage(): string
    {
        return match ($this->type) {
            'upcoming' => "「{$this->readingPlan->book->title}」の読書期限まであと3日です。",
            'final' => "「{$this->readingPlan->book->title}」の読書期限は本日までです。",
            're_engagement' => "「{$this->readingPlan->book->title}」の読書期限を過ぎています。計画を見直しませんか？",
        };
    }
}
