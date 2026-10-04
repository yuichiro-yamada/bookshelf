<?php

namespace Tests\Feature\BookCrud;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍削除機能のテスト（テストケース一覧 3-5「書籍削除」に対応）
 */
class BookDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 3-5-1 未ログインの場合、書籍を削除できない
     */
    public function test_guest_cannot_delete_book(): void
    {
        $book = Book::factory()->for(User::factory())->create();

        $response = $this->delete(route('books.destroy', $book));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    /**
     * 3-5-2 他人が登録した書籍は削除できない
     */
    public function test_other_user_cannot_delete_book(): void
    {
        $otherUser = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();

        // 詳細ページに「削除」ボタン（削除フォーム）が表示されない
        $this->actingAs($otherUser)->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee('action="'.route('books.destroy', $book).'"', false);

        // 直接リクエストを送っても403になる
        $response = $this->actingAs($otherUser)->delete(route('books.destroy', $book));

        $response->assertForbidden();
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    /**
     * 3-5-3 削除ボタンを押すと確認ダイアログが表示される
     *
     * ダイアログ自体はブラウザのJavaScript（confirm）で表示されるため、
     * 削除フォームに確認ダイアログの処理が設定されていることを確認する。
     */
    public function test_delete_button_shows_confirmation_dialog(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($owner)->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee(
            'action="'.route('books.destroy', $book).'" method="POST" onsubmit="return confirm(\'本当に削除しますか？\')"',
            false
        );
    }

    /**
     * 3-5-4 確認ダイアログでOKを押すと書籍が削除される
     */
    public function test_owner_can_delete_book(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($owner)->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));
        $response->assertSessionHas('success', '書籍を削除しました。');
        $this->assertDatabaseMissing('books', ['id' => $book->id]);

        $this->get(route('books.index'))->assertSee('書籍を削除しました。');
    }
}
