<?php

namespace Tests\Unit\Models;

use App\Enums\ReadingPlanStatus;
use PHPUnit\Framework\TestCase;

/**
 * ReadingPlanStatus（Enum）の単体テスト（テストケース一覧 1-5「ReadingPlanStatus(Enum)」に対応）
 *
 * DBやLaravelのアプリケーションを必要としないため、PHPUnit の TestCase を直接使う。
 */
class ReadingPlanStatusTest extends TestCase
{
    /**
     * 1-5-1 各ステータスのlabel()・badgeClass()が正しい値を返す
     */
    public function test_label_and_badge_class_return_expected_values(): void
    {
        $this->assertSame('進行中', ReadingPlanStatus::InProgress->label());
        $this->assertSame('完了', ReadingPlanStatus::Completed->label());
        $this->assertSame('期限切れ', ReadingPlanStatus::Expired->label());

        $this->assertSame('bg-blue-100 text-blue-800', ReadingPlanStatus::InProgress->badgeClass());
        $this->assertSame('bg-green-100 text-green-800', ReadingPlanStatus::Completed->badgeClass());
        $this->assertSame('bg-red-100 text-red-800', ReadingPlanStatus::Expired->badgeClass());
    }
}
