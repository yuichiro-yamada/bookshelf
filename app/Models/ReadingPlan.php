<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ReadingPlan extends Model
{
    use HasFactory;

    /**
     * テーブル名
     *
     * @var string
     */
    protected $table = 'reading_plans';

    /**
     * マスアサイン可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'book_id',
        'user_id',
        'target_date',
        'completed_at',
    ];

    /**
     * 属性のキャスト定義
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target_date' => 'date',
        'completed_at' => 'date',
    ];

    /**
     * この読書計画の対象書籍
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * この読書計画を作成したユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 状態（target_date / completed_at から動的に判定）
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn (): ReadingPlanStatus => match (true) {
                /*　completed_at に日付が入っていれば Completed（完了）　*/
                $this->completed_at !== null => ReadingPlanStatus::Completed,
                /*　未完了かつ、target_date（目標期日）が今日より前（lt = less than）なら Overdue（期限切れ）　*/
                $this->target_date->lt(Carbon::today()) => ReadingPlanStatus::Overdue,
                /*　それ以外（今日以降が期限）なら InProgress（進行中）*/
                default => ReadingPlanStatus::InProgress,
            },
        );
    }
}
