<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'status',
    ];

    /**
     * 属性のキャスト定義
     *
     * status は ReadingPlanStatus（バックドEnum）に自動キャストされる。
     * 取得時は ReadingPlanStatus インスタンス、保存時は文字列（value）として扱われる。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target_date' => 'date',
        'completed_at' => 'datetime',
        'status' => ReadingPlanStatus::class,
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
}
