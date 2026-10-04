<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ヘッダーの未読件数のテスト（テストケース一覧 21-3「ヘッダーの未読件数」に対応）
 */
class NotificationBadgeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ベルアイコンの未読件数バッジのHTML（ナビゲーションの span と同じクラス）
     */
    private const BADGE = 'text-white bg-red-600 rounded-full">';

    private function createNotification(User $user, ?string $readAt = null): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => ReadingPlanReminder::class,
            'data' => [
                'plan_id' => 1,
                'book_title' => 'テスト書籍',
                'timing' => 'three_days_before',
                'title' => '読書期限のお知らせ',
                'body' => '「テスト書籍」の読書期限まであと3日です。',
            ],
            'read_at' => $readAt,
        ]);
    }

    /**
     * 21-3-1 ヘッダーのベルアイコンに未読件数が表示される
     */
    public function test_unread_count_badge_is_shown_only_when_unread_exists(): void
    {
        $user = User::factory()->create();
        $this->createNotification($user);
        $this->createNotification($user);
        $this->createNotification($user, '2026-09-01 10:00:00'); // 既読は数えない

        $this->actingAs($user)->get(route('books.index'))
            ->assertOk()
            ->assertSee(self::BADGE.'2</span>', false);

        // 未読通知が0件のユーザーにはバッジが表示されない
        $userWithoutUnread = User::factory()->create();
        $this->createNotification($userWithoutUnread, '2026-09-01 10:00:00');

        $this->actingAs($userWithoutUnread)->get(route('books.index'))
            ->assertOk()
            ->assertDontSee(self::BADGE, false);
    }

    /**
     * 21-3-2 通知を既読にすると、ヘッダーの未読件数が減る
     */
    public function test_unread_count_decreases_after_marking_as_read(): void
    {
        $user = User::factory()->create();
        $target = $this->createNotification($user);
        $this->createNotification($user);
        $this->createNotification($user);

        $this->actingAs($user)->get(route('books.index'))->assertSee(self::BADGE.'3</span>', false);

        $this->actingAs($user)->post(route('notifications.read', $target->id))->assertRedirect();

        $this->actingAs($user)->get(route('books.index'))
            ->assertSee(self::BADGE.'2</span>', false)
            ->assertDontSee(self::BADGE.'3</span>', false);
    }

    /**
     * 21-3-3 他ユーザーの通知は未読件数に含まれない
     */
    public function test_other_users_notifications_are_not_counted(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->createNotification($user);
        $this->createNotification($otherUser);
        $this->createNotification($otherUser);

        $this->actingAs($user)->get(route('books.index'))
            ->assertSee(self::BADGE.'1</span>', false)
            ->assertDontSee(self::BADGE.'3</span>', false);
    }
}
