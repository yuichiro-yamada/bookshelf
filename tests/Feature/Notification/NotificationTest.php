<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 通知の既読化のテスト（テストケース一覧 21-1「通知の既読化」に対応）
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 指定ユーザー宛ての通知（notificationsテーブルのレコード）を作成する
     */
    private function createNotification(User $user, string $bookTitle = 'テスト書籍', ?string $readAt = null): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => ReadingPlanReminder::class,
            'data' => [
                'plan_id' => 1,
                'book_title' => $bookTitle,
                'timing' => 'three_days_before',
                'title' => '読書期限のお知らせ',
                'body' => "「{$bookTitle}」の読書期限まであと3日です。",
            ],
            'read_at' => $readAt,
        ]);
    }

    /**
     * 21-1-1 未ログインの場合、通知の既読化操作にアクセスできない
     */
    public function test_guest_cannot_mark_notification_as_read(): void
    {
        $notification = $this->createNotification(User::factory()->create());

        $response = $this->post(route('notifications.read', $notification->id));

        $response->assertRedirect(route('login'));
        $this->assertNull($notification->fresh()->read_at);
    }

    /**
     * 21-1-2 自分の通知を既読にできる
     */
    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = $this->createNotification($user, '既読にする書籍');

        $response = $this->actingAs($user)
            ->from(route('notifications.index'))
            ->post(route('notifications.read', $notification->id));

        $response->assertRedirect(route('notifications.index'));
        $response->assertSessionHas('success', '「既読にする書籍」の通知を既読にしました。');
        $this->assertNotNull($notification->fresh()->read_at);

        // 画面上部にメッセージが表示され、未読表示が消えている
        $this->get(route('notifications.index'))
            ->assertSee('「既読にする書籍」の通知を既読にしました。')
            ->assertDontSee('未読')
            ->assertDontSee(route('notifications.read', $notification->id));
    }

    /**
     * 21-1-3 他ユーザーの通知は既読にできない
     */
    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $othersNotification = $this->createNotification(User::factory()->create());

        $response = $this->actingAs($user)->post(route('notifications.read', $othersNotification->id));

        $response->assertNotFound();
        $this->assertNull($othersNotification->fresh()->read_at);
    }
}
