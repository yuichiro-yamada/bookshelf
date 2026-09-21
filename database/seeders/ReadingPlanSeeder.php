<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    /**
     * reading_plansテーブルに読書計画データを6件登録する
     *
     * 実行した日にかかわらず同じ挙動を確認できるよう、期日は今日を起点に設定する。
     * 動作確認を効率よく行うため、主要なシナリオは山田太郎に集約する。
     * 同じユーザーの「進行中」の計画が同じ書籍で重複しないよう、計画ごとに異なる書籍を割り当てる。
     */
    public function run(): void
    {
        $today = Carbon::today();

        $yamada = User::where('email', 'yamada@example.com')->firstOrFail();
        $suzuki = User::where('email', 'suzuki@example.com')->firstOrFail();

        $books = Book::orderBy('id')->take(6)->get();

        $plans = [
            // 山田太郎：期日の3日前 → 予告リマインダーの対象
            [$yamada, $books[0], $today->copy()->addDays(3), ReadingPlanStatus::InProgress, null],
            // 山田太郎：期日当日 → 最終警告リマインダーの対象
            [$yamada, $books[1], $today->copy(), ReadingPlanStatus::InProgress, null],
            // 山田太郎：期日の3日後 → バッチで「期限切れ」に更新され、再エンゲージメント通知の対象にもなる
            [$yamada, $books[2], $today->copy()->subDays(3), ReadingPlanStatus::InProgress, null],
            // 山田太郎：期日の7日前 → リマインダーの対象外
            [$yamada, $books[3], $today->copy()->addDays(7), ReadingPlanStatus::InProgress, null],
            // 山田太郎：完了済み
            [$yamada, $books[4], $today->copy()->subDays(10), ReadingPlanStatus::Completed, $today->copy()->subDays(5)],
            // 鈴木花子：山田太郎でログインし、/reading-plans/6/edit を直接開いて403になることの確認用
            [$suzuki, $books[5], $today->copy()->addDays(5), ReadingPlanStatus::InProgress, null],
        ];

        foreach ($plans as [$user, $book, $targetDate, $status, $completedAt]) {
            ReadingPlan::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'target_date' => $targetDate,
                'status' => $status,
                'completed_at' => $completedAt,
            ]);
        }
    }
}
