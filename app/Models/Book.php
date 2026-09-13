<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    /**
     * テーブル名
     *
     * @var string
     */
    protected $table = 'books';

    /**
     * マスアサイン可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'author',
        'isbn',
        'published_date',
        'description',
        'image_url',
        'user_id',
    ];

    /**
     * 属性のキャスト定義
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_date' => 'date',
    ];

    /**
     * この書籍を登録したユーザー（作成者）
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この書籍に紐づくジャンル
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'book_genres')
            ->withTimestamps();
    }

    /**
     * この書籍に投稿されたレビュー
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    /**
     * この書籍をお気に入り登録しているユーザー
     */
    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')
            ->withTimestamps();
    }

    /**
     * この書籍に紐づく読書計画
     */
    public function readingPlans(): HasMany
    {
        return $this->hasMany(ReadingPlan::class);
    }

    /**
     * タイトル・著者名を対象に、キーワードを部分一致（LIKE）で検索する
     *
     * 画面用の一覧表示（Book\BookController@index）とAPI用の一覧取得
     * （Api\V1\Book\BookController@index）の両方から利用される、共通の絞り込み条件。
     * $keyword が空文字列・null の場合は何もしない。
     */
    public function scopeSearchKeyword(Builder $query, mixed $keyword): Builder
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return $query;
        }

        $escaped = addcslashes($keyword, '%_\\');

        return $query->where(function (Builder $q) use ($escaped) {
            $q->where('title', 'like', "%{$escaped}%")
                ->orWhere('author', 'like', "%{$escaped}%");
        });
    }

    /**
     * 指定したジャンルIDに紐づく書籍のみに絞り込む
     *
     * 画面用・API用の一覧取得の両方から利用される、共通の絞り込み条件。
     * $genreId が未指定（null・空文字列など）の場合は何もしない。
     */
    public function scopeFilterByGenre(Builder $query, mixed $genreId): Builder
    {
        if (blank($genreId)) {
            return $query;
        }

        return $query->whereHas('genres', function (Builder $q) use ($genreId) {
            $q->where('genres.id', $genreId);
        });
    }
}
