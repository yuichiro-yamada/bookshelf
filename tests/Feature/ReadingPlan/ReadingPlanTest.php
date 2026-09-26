<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 読書計画の作成・一覧・読了・削除のテスト
 * （テストケース一覧 17-1「読書計画の作成・一覧・読了・削除」に対応）
 */
class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 17-1-1 未ログインの場合、読書計画関連ページ・操作にアクセスできない
     */
    public function test_guest_cannot_access_reading_plan_pages_and_actions(): void
    {
        $plan = ReadingPlan::factory()->create();
        $book = Book::factory()->create();

        $this->get(route('reading-plans.index'))->assertRedirect(route('login'));
        $this->get(route('reading-plans.create'))->assertRedirect(route('login'));
        $this->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->toDateString(),
        ])->assertRedirect(route('login'));
        $this->get(route('reading-plans.edit', $plan))->assertRedirect(route('login'));
        $this->put(route('reading-plans.update', $plan), [
            'target_date' => now()->addWeek()->toDateString(),
        ])->assertRedirect(route('login'));
        $this->post(route('reading-plans.complete', $plan))->assertRedirect(route('login'));
        $this->delete(route('reading-plans.destroy', $plan))->assertRedirect(route('login'));

        $this->assertDatabaseCount('reading_plans', 1);
        $this->assertDatabaseHas('reading_plans', ['id' => $plan->id, 'status' => 'in_progress']);
    }

    /**
     * 17-1-2 読書計画一覧には自分が作成した計画のみが期日の昇順で表示される
     */
    public function test_index_shows_only_own_plans_ordered_by_target_date(): void
    {
        $user = User::factory()->create();
        $later = ReadingPlan::factory()->for($user)
            ->for(Book::factory()->create(['title' => '期日が遅い書籍']))
            ->create(['target_date' => now()->addDays(10)->toDateString()]);
        $sooner = ReadingPlan::factory()->for($user)
            ->for(Book::factory()->create(['title' => '期日が早い書籍']))
            ->create(['target_date' => now()->addDays(2)->toDateString()]);
        ReadingPlan::factory()
            ->for(Book::factory()->create(['title' => '他人の計画の書籍']))
            ->create();

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertOk();
        $response->assertViewHas('readingPlans', fn ($plans) => $plans->pluck('id')->all() === [$sooner->id, $later->id]);
        $response->assertSeeInOrder(['期日が早い書籍', '期日が遅い書籍']);
        $response->assertDontSee('他人の計画の書籍');
    }

    /**
     * 17-1-3 ステータスで絞り込んで表示できる
     */
    public function test_index_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $inProgress = ReadingPlan::factory()->for($user)->create();
        $completed = ReadingPlan::factory()->for($user)->completed()->create();
        $expired = ReadingPlan::factory()->for($user)->expired()->create();

        $expectations = [
            'in_progress' => $inProgress->id,
            'completed' => $completed->id,
            'expired' => $expired->id,
        ];

        foreach ($expectations as $status => $expectedId) {
            $response = $this->actingAs($user)->get(route('reading-plans.index', ['status' => $status]));

            $response->assertOk();
            $response->assertViewHas('readingPlans', fn ($plans) => $plans->pluck('id')->all() === [$expectedId]);
        }
    }

    /**
     * 17-1-4 既に「進行中」の計画がある書籍は作成画面の選択肢から除外される
     */
    public function test_books_with_in_progress_plan_are_excluded_from_create_form(): void
    {
        $user = User::factory()->create();
        $activeBook = Book::factory()->create(['title' => '進行中の計画がある書籍']);
        $freeBook = Book::factory()->create(['title' => '計画のない書籍']);
        ReadingPlan::factory()->for($user)->for($activeBook)->create();

        $response = $this->actingAs($user)->get(route('reading-plans.create'));

        $response->assertOk();
        $response->assertViewHas('books', fn ($books) => ! $books->contains('id', $activeBook->id) && $books->contains('id', $freeBook->id));
        $response->assertDontSee('進行中の計画がある書籍');
        $response->assertSee('計画のない書籍');
    }

    /**
     * 17-1-5 同じ書籍に進行中の計画がある状態で作成しようとすると認可エラーになる
     */
    public function test_cannot_create_plan_when_in_progress_plan_exists(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        ReadingPlan::factory()->for($user)->for($book)->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('reading_plans', 1);
    }

    /**
     * 17-1-6 完了済みの計画がある書籍は再度計画を作成できる
     */
    public function test_can_create_plan_when_only_completed_plan_exists(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        ReadingPlan::factory()->for($user)->for($book)->completed()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertSame(2, ReadingPlan::where('user_id', $user->id)->where('book_id', $book->id)->count());
        $this->assertDatabaseHas('reading_plans', ['user_id' => $user->id, 'book_id' => $book->id, 'status' => 'in_progress']);
    }

    /**
     * 17-1-7 期日が入力されていない場合、バリデーションエラーが表示される
     */
    public function test_target_date_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => '',
        ]);

        $response->assertSessionHasErrors(['target_date' => '期日を入力してください']);
        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * 17-1-8 期日に今日より前の日付を指定した場合、バリデーションエラーが表示される
     */
    public function test_target_date_must_not_be_in_the_past(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => now()->subDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['target_date' => '期日には今日以降の日付を指定してください']);
        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * 17-1-9 全ての項目が正しく入力されている場合、読書計画が作成される
     */
    public function test_plan_is_created_with_valid_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['title' => '計画する書籍']);
        $targetDate = now()->addWeek()->toDateString();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => $targetDate,
        ]);

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '「計画する書籍」の読書計画を作成しました。');
        $plan = ReadingPlan::where('user_id', $user->id)->where('book_id', $book->id)->firstOrFail();
        $this->assertSame($targetDate, $plan->target_date->toDateString());
        $this->assertSame(ReadingPlanStatus::InProgress, $plan->status);

        $this->get(route('reading-plans.index'))->assertSee('「計画する書籍」の読書計画を作成しました。');
    }

    /**
     * 17-1-10 「読了」操作を行うとステータスが「完了」になりcompleted_atが記録される
     */
    public function test_complete_marks_plan_as_completed(): void
    {
        Carbon::setTestNow('2026-09-26 12:34:56');
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('reading-plans.complete', $plan));

        $response->assertRedirect(route('reading-plans.index'));
        $plan->refresh();
        $this->assertSame(ReadingPlanStatus::Completed, $plan->status);
        $this->assertSame('2026-09-26 12:34:56', $plan->completed_at->format('Y-m-d H:i:s'));
    }

    /**
     * 17-1-11 他人が作成した読書計画は読了・削除できない
     */
    public function test_other_user_cannot_complete_or_delete_plan(): void
    {
        $otherUser = User::factory()->create();
        $plan = ReadingPlan::factory()->create();

        $this->actingAs($otherUser)->post(route('reading-plans.complete', $plan))->assertForbidden();
        $this->actingAs($otherUser)->delete(route('reading-plans.destroy', $plan))->assertForbidden();

        $this->assertDatabaseHas('reading_plans', ['id' => $plan->id, 'status' => 'in_progress', 'completed_at' => null]);
    }

    /**
     * 17-1-12 自分が作成した読書計画は削除できる
     */
    public function test_owner_can_delete_plan(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('reading-plans.destroy', $plan));

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '読書計画を削除しました。');
        $this->assertDatabaseMissing('reading_plans', ['id' => $plan->id]);

        $this->get(route('reading-plans.index'))->assertSee('読書計画を削除しました。');
    }

    /**
     * 17-1-13 読書計画削除時に、関連するリマインダー通知も同時に削除される
     */
    public function test_related_reminder_notifications_are_deleted_with_plan(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->create();
        $otherPlan = ReadingPlan::factory()->for($user)->create();

        $user->notify(new ReadingPlanReminder($plan, 'upcoming'));
        $user->notify(new ReadingPlanReminder($plan, 'final'));
        $user->notify(new ReadingPlanReminder($otherPlan, 'upcoming'));
        $this->assertDatabaseCount('notifications', 3);

        $response = $this->actingAs($user)->delete(route('reading-plans.destroy', $plan));

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseMissing('reading_plans', ['id' => $plan->id]);

        // 削除した計画の通知だけが消え、別の計画の通知は残る
        $remaining = $user->fresh()->notifications;
        $this->assertCount(1, $remaining);
        $this->assertSame($otherPlan->id, (int) $remaining->first()->data['plan_id']);
    }

    /**
     * 17-1-14 ステータスが「完了」の計画は、読了操作を行えない
     */
    public function test_completed_plan_cannot_be_completed_again(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->create([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => '2026-01-01 10:00:00',
        ]);

        $response = $this->actingAs($user)->post(route('reading-plans.complete', $plan));

        $response->assertForbidden();
        $this->assertSame('2026-01-01 10:00:00', $plan->fresh()->completed_at->format('Y-m-d H:i:s'));
    }

    /**
     * 17-1-15 「期限切れ」の計画がある書籍は、再度計画を作成できる
     */
    public function test_can_create_plan_when_only_expired_plan_exists(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $expiredPlan = ReadingPlan::factory()->for($user)->for($book)->expired()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseHas('reading_plans', ['user_id' => $user->id, 'book_id' => $book->id, 'status' => 'in_progress']);
        // 「期限切れ」の計画はそのまま残る
        $this->assertDatabaseHas('reading_plans', ['id' => $expiredPlan->id, 'status' => 'expired']);
    }

    /**
     * 17-1-16 書籍が選択されていない場合、バリデーションメッセージが表示される
     */
    public function test_book_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => '',
            'target_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['book_id' => '書籍を選択してください']);
    }

    /**
     * 17-1-17 存在しない書籍IDを指定した場合、バリデーションメッセージが表示される
     */
    public function test_book_must_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => 999,
            'target_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['book_id' => '選択された書籍が見つかりません']);
    }

    /**
     * 17-1-18 書籍IDが整数でない場合、バリデーションメッセージが表示される
     */
    public function test_book_id_must_be_integer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => 'abc',
            'target_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['book_id' => '書籍の指定が正しくありません']);
    }

    /**
     * 17-1-19 期日が日付形式でない場合、バリデーションメッセージが表示される
     */
    public function test_target_date_must_be_a_date(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => 'abc',
        ]);

        $response->assertSessionHasErrors(['target_date' => '正しい日付を入力してください']);
    }

    /**
     * 17-1-20 期日に今日の日付を指定した場合、計画を作成できる
     */
    public function test_plan_can_be_created_with_today_as_target_date(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['title' => '今日が期日の書籍']);

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '「今日が期日の書籍」の読書計画を作成しました。');
        $this->assertDatabaseCount('reading_plans', 1);
    }
}
