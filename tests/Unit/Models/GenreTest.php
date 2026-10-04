<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Genreモデルの単体テスト（テストケース一覧 1-2「Genreモデル」に対応）
 */
class GenreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1-2-1 書籍との多対多関連が取得できる
     */
    public function test_books_relation_returns_attached_books(): void
    {
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(2)->create();
        Book::factory()->create(); // 紐づけない書籍
        $genre->books()->attach($books);

        $this->assertEqualsCanonicalizing(
            $books->pluck('id')->all(),
            $genre->books->pluck('id')->all()
        );
    }
}
