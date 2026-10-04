<?php

namespace Tests\Feature\BookAuthorization;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use App\Policies\BookPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BookPolicyの認可ロジックのテスト（テストケース一覧 13-1「BookPolicyの認可ロジック」に対応）
 */
class BookAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private BookPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new BookPolicy;
    }

    /**
     * 13-1-1 ログインユーザーは誰でも書籍を作成できる
     */
    public function test_any_user_can_create_book(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($user->can('create', Book::class));
    }

    /**
     * 13-1-2 書籍の登録者本人は更新が許可される
     */
    public function test_owner_can_update_book(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $this->assertTrue($this->policy->update($owner, $book));
    }

    /**
     * 13-1-3 書籍の登録者以外は更新が許可されない
     */
    public function test_non_owner_cannot_update_book(): void
    {
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $this->assertFalse($this->policy->update($otherUser, $book));
    }

    /**
     * 13-1-4 書籍の登録者本人は削除が許可される
     */
    public function test_owner_can_delete_book(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $this->assertTrue($this->policy->delete($owner, $book));
    }

    /**
     * 13-1-5 書籍の登録者以外は削除が許可されない
     */
    public function test_non_owner_cannot_delete_book(): void
    {
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $this->assertFalse($this->policy->delete($otherUser, $book));
    }

    /**
     * 13-1-6 フォームを介さない直接のPUT/DELETEリクエストでも他人の書籍は操作できない
     */
    public function test_direct_put_and_delete_requests_are_forbidden_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['title' => '他人の書籍']);

        $this->actingAs($otherUser)->put(route('books.update', $book), [
            'title' => '書き換えたタイトル',
            'author' => '書き換えた著者',
            'isbn' => '9784000000009',
            'published_date' => '2020-01-01',
            'genres' => [$genre->id],
        ])->assertForbidden();

        $this->actingAs($otherUser)->delete(route('books.destroy', $book))->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '他人の書籍']);
    }

    /**
     * 13-1-7 他人の書籍に対して不正な入力を直接PUTしても、入力エラーではなく403になる
     */
    public function test_authorization_is_checked_before_validation(): void
    {
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['title' => '他人の書籍']);

        $response = $this->actingAs($otherUser)->put(route('books.update', $book), [
            'title' => '',
            'author' => '',
            'genres' => [],
        ]);

        $response->assertForbidden();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '他人の書籍']);
    }
}
