<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 通知一覧のテスト（テストケース一覧 21-2「通知一覧」に対応）
 */
class NotificationIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 指定ユーザー宛ての通知（notificationsテーブルのレコード）を作成する
     */
    private function createNotification(User $user, string $title, string $body, ?string $createdAt = null, ?string $readAt = null): DatabaseNotification
    {
        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => ReadingPlanReminder::class,
            'data' => [
                'plan_id' => 1,
                'book_title' => 'テスト書籍',
                'timing' => 'three_days_before',
                'title' => $title,
                'body' => $body,
            ],
            'read_at' => $readAt,
        ]);

        if ($createdAt !== null) {
            $notification->forceFill(['created_at' => $createdAt])->save();
        }

        return $notification;
    }

    /**
     * 21-2-1 未ログインの場合、通知一覧にアクセスできない
     */
    public function test_guest_cannot_access_notification_list(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    /**
     * 21-2-2 通知一覧には自分宛ての通知のみが新しい順に表示される
     */
    public function test_only_own_notifications_are_listed_in_newest_order(): void
    {
        $user = User::factory()->create();
        $this->createNotification($user, '古い通知', '古い本文', '2026-09-01 10:00:00');
        $this->createNotification($user, '新しい通知', '新しい本文', '2026-09-03 10:00:00');
        $this->createNotification($user, '中間の通知', '中間の本文', '2026-09-02 10:00:00');
        $this->createNotification(User::factory()->create(), '他人の通知', '他人の本文');

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['新しい通知', '中間の通知', '古い通知']);
        $response->assertDontSee('他人の通知');
    }

    /**
     * 21-2-3 通知が1件もない場合の表示を確認する
     */
    public function test_shows_message_when_no_notifications(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('通知はありません。');
    }

    /**
     * 21-2-4 通知のタイトル・本文が表示される
     */
    public function test_notification_title_and_body_are_displayed(): void
    {
        $user = User::factory()->create();
        $this->createNotification($user, '読書期限のお知らせ', '「吾輩は猫である」の読書期限まであと3日です。');

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('読書期限のお知らせ');
        $response->assertSee('「吾輩は猫である」の読書期限まであと3日です。');
    }

    /**
     * 21-2-5 未読と既読で表示が異なる
     */
    public function test_unread_and_read_notifications_are_displayed_differently(): void
    {
        $user = User::factory()->create();
        $unread = $this->createNotification($user, '未読の通知', '未読の本文');
        $read = $this->createNotification($user, '既読の通知', '既読の本文', null, '2026-09-01 10:00:00');

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $content = $response->getContent();
        // 「未読」バッジと「既読にする」ボタンは、未読の通知1件分だけ表示される
        $this->assertSame(1, substr_count($content, '>未読</span>'));
        $this->assertSame(1, substr_count($content, '既読にする'));
        $response->assertSee('action="'.route('notifications.read', $unread->id).'"', false);
        $response->assertDontSee('action="'.route('notifications.read', $read->id).'"', false);
    }
}
