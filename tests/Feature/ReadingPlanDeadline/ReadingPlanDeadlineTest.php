<?php

namespace Tests\Feature\ReadingPlanDeadline;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 読書計画の期日変更のテスト（テストケース一覧 18-1「読書計画の期日変更」に対応）
 */
class ReadingPlanDeadlineTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 18-1-1 未ログインの場合、読書計画編集ページにアクセスできない
     */
    public function test_guest_cannot_access_edit_page(): void
    {
        $plan = ReadingPlan::factory()->create();

        $this->get(route('reading-plans.edit', $plan))->assertRedirect(route('login'));
    }

    /**
     * 18-1-2 他人が作成した読書計画の期日は変更できない
     */
    public function test_other_user_cannot_update_target_date(): void
    {
        $otherUser = User::factory()->create();
        $plan = ReadingPlan::factory()->create(['target_date' => now()->addWeek()->toDateString()]);
        $originalDate = $plan->target_date->toDateString();

        $response = $this->actingAs($otherUser)->put(route('reading-plans.update', $plan), [
            'target_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertForbidden();
        $this->assertSame($originalDate, $plan->fresh()->target_date->toDateString());
    }

    /**
     * 18-1-3 編集画面を開くと現在の期日が初期表示される
     */
    public function test_edit_page_shows_current_target_date(): void
    {
        $user = User::factory()->create();
        $targetDate = now()->addDays(10)->toDateString();
        $plan = ReadingPlan::factory()->for($user)->create(['target_date' => $targetDate]);

        $response = $this->actingAs($user)->get(route('reading-plans.edit', $plan));

        $response->assertOk();
        $response->assertSee('value="'.$targetDate.'"', false);
    }

    /**
     * 18-1-4 期日が入力されていない場合、バリデーションエラーが表示される
     */
    public function test_target_date_is_required(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->create();

        $response = $this->actingAs($user)->put(route('reading-plans.update', $plan), ['target_date' => '']);

        $response->assertSessionHasErrors(['target_date' => '期日を入力してください']);
    }

    /**
     * 18-1-5 期日に今日より前の日付を指定した場合、バリデーションエラーが表示される
     */
    public function test_target_date_must_not_be_in_the_past(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->create();
        $originalDate = $plan->target_date->toDateString();

        $response = $this->actingAs($user)->put(route('reading-plans.update', $plan), [
            'target_date' => now()->subDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['target_date' => '期日には今日以降の日付を指定してください']);
        $this->assertSame($originalDate, $plan->fresh()->target_date->toDateString());
    }

    /**
     * 18-1-6 期日を正しく変更すると、計画が更新されステータスが「進行中」に戻る
     */
    public function test_valid_update_changes_target_date_and_resets_status_to_in_progress(): void
    {
        $user = User::factory()->create();
        // 「期限切れ」の計画（同じ書籍に「進行中」の計画はない）
        $plan = ReadingPlan::factory()->for($user)->expired()->create();
        $newDate = now()->addDays(14)->toDateString();

        $response = $this->actingAs($user)->put(route('reading-plans.update', $plan), ['target_date' => $newDate]);

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '読書計画を更新しました。');
        $plan->refresh();
        $this->assertSame($newDate, $plan->target_date->toDateString());
        $this->assertSame(ReadingPlanStatus::InProgress, $plan->status);

        $this->get(route('reading-plans.index'))->assertSee('読書計画を更新しました。');
    }

    /**
     * 18-1-7 一覧・編集画面を表示しても、期日を過ぎた「進行中」の計画のステータスは変更されない
     */
    public function test_viewing_pages_does_not_expire_overdue_plans(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->create([
            'target_date' => now()->subDays(2)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->actingAs($user)->get(route('reading-plans.index'))->assertOk();
        $this->assertSame(ReadingPlanStatus::InProgress, $plan->fresh()->status);

        $this->actingAs($user)->get(route('reading-plans.edit', $plan))->assertOk();
        $this->assertSame(ReadingPlanStatus::InProgress, $plan->fresh()->status);
    }

    /**
     * 18-1-8 ステータスが「完了」の計画は期日を変更できない
     */
    public function test_completed_plan_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->completed()->create();
        $originalDate = $plan->target_date->toDateString();

        $response = $this->actingAs($user)->put(route('reading-plans.update', $plan), [
            'target_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertForbidden();
        $this->assertSame($originalDate, $plan->fresh()->target_date->toDateString());
        $this->assertSame(ReadingPlanStatus::Completed, $plan->fresh()->status);
    }

    /**
     * 18-1-9 ステータスが「完了」の計画は、編集ページにも直接アクセスできない
     */
    public function test_completed_plan_edit_page_is_forbidden(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->completed()->create();

        $this->actingAs($user)->get(route('reading-plans.edit', $plan))->assertForbidden();
    }

    /**
     * 18-1-10 他人が作成した読書計画の編集ページにはアクセスできない
     */
    public function test_other_users_plan_edit_page_is_forbidden(): void
    {
        $otherUser = User::factory()->create();
        $plan = ReadingPlan::factory()->create();

        $this->actingAs($otherUser)->get(route('reading-plans.edit', $plan))->assertForbidden();
    }

    /**
     * 18-1-11 同じ書籍に「進行中」の計画がある場合、「期限切れ」の計画は編集・更新できない
     */
    public function test_expired_plan_cannot_be_edited_when_in_progress_plan_exists_for_same_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $expiredPlan = ReadingPlan::factory()->for($user)->for($book)->expired()->create();
        ReadingPlan::factory()->for($user)->for($book)->create(); // 同じ書籍の「進行中」の計画
        $originalDate = $expiredPlan->target_date->toDateString();

        $this->actingAs($user)->get(route('reading-plans.edit', $expiredPlan))->assertForbidden();

        $this->actingAs($user)->put(route('reading-plans.update', $expiredPlan), [
            'target_date' => now()->addWeek()->toDateString(),
        ])->assertForbidden();

        $expiredPlan->refresh();
        $this->assertSame(ReadingPlanStatus::Expired, $expiredPlan->status);
        $this->assertSame($originalDate, $expiredPlan->target_date->toDateString());
        $this->assertSame(1, ReadingPlan::where('book_id', $book->id)->where('status', 'in_progress')->count());
    }
}
