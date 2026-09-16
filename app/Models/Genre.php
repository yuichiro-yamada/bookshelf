<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    use HasFactory;

    /**
     * テーブル名
     *
     * @var string
     */
    protected $table = 'genres';

    /**
     * マスアサイン可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * このジャンルに属する書籍
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class)
            ->withTimestamps();
    }
}
