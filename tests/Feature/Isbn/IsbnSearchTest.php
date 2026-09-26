<?php

namespace Tests\Feature\Isbn;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ISBN検索（Google Books API連携）のテスト
 * （テストケース一覧 14-1「ISBNからの書籍情報取得(外部API連携)」に対応）
 *
 * 外部APIへは実際に通信しないよう、すべて Http::fake() でモック化する。
 */
class IsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    private const ISBN = '9784000000000';

    protected function setUp(): void
    {
        parent::setUp();

        // モック化していないURLへの通信があった場合はテストを失敗させる
        Http::preventStrayRequests();
    }

    /**
     * 14-1-1 未ログインの場合、ISBN検索エンドポイントにアクセスできない
     */
    public function test_guest_cannot_access_isbn_search(): void
    {
        Http::fake();

        $response = $this->get('/books/isbn/'.self::ISBN);

        $response->assertRedirect(route('login'));
        Http::assertNothingSent();
    }

    /**
     * 14-1-2 ISBNが空(空白のみ)の場合、バリデーションエラーが返る
     *
     * ISBN部分を完全に空にすると /books/isbn となりこのルートに一致しないため、
     * 半角スペース（%20）で空入力を再現する。
     */
    public function test_blank_isbn_returns_validation_error(): void
    {
        Http::fake();

        $response = $this->actingAs(User::factory()->create())->getJson('/books/isbn/%20');

        $response->assertUnprocessable();
        $response->assertExactJson(['error' => 'ISBNを入力してください']);
        Http::assertNothingSent();
    }

    /**
     * 14-1-3 ISBNが13桁の数字でない場合、バリデーションエラーが返る
     */
    public function test_isbn_must_be_13_digits(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/books/isbn/12345')
            ->assertUnprocessable()
            ->assertExactJson(['error' => 'ISBNは13桁の数字で入力してください']);

        $this->actingAs($user)->getJson('/books/isbn/978400000000X')
            ->assertUnprocessable()
            ->assertExactJson(['error' => 'ISBNは13桁の数字で入力してください']);

        Http::assertNothingSent();
    }

    /**
     * 14-1-4 既に登録済みのISBNの場合、バリデーションエラーが返る
     */
    public function test_registered_isbn_returns_validation_error(): void
    {
        Http::fake();
        Book::factory()->create(['isbn' => self::ISBN]);

        $response = $this->actingAs(User::factory()->create())->getJson('/books/isbn/'.self::ISBN);

        $response->assertUnprocessable();
        $response->assertExactJson(['error' => 'このISBNはすでに登録されています']);
        Http::assertNothingSent();
    }

    /**
     * 14-1-5 Google Books APIをHttp::fake()でモック化し、書籍が見つかった場合に情報が返る
     */
    public function test_book_information_is_returned_when_found(): void
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'モックの書籍',
                            'authors' => ['著者A', '著者B'],
                            'description' => 'モックの説明文',
                            'publishedDate' => '2020-04-01',
                            'imageLinks' => ['thumbnail' => 'https://example.com/thumb.jpg'],
                            'industryIdentifiers' => [
                                ['type' => 'ISBN_10', 'identifier' => '4000000000'],
                                ['type' => 'ISBN_13', 'identifier' => self::ISBN],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs(User::factory()->create())->getJson('/books/isbn/'.self::ISBN);

        $response->assertOk();
        $response->assertExactJson([
            'title' => 'モックの書籍',
            'author' => '著者A, 著者B',
            'description' => 'モックの説明文',
            'image_url' => 'https://example.com/thumb.jpg',
            'published_date' => '2020-04-01',
        ]);
        Http::assertSent(fn ($request) => str_contains(urldecode($request->url()), 'q=isbn:'.self::ISBN));
    }

    /**
     * 14-1-6 Http::fake()でitemsが空(該当書籍なし)の場合、404エラーが返る
     */
    public function test_returns_404_when_no_items_found(): void
    {
        Http::fake([
            '*' => Http::response(['totalItems' => 0, 'items' => []], 200),
        ]);

        $response = $this->actingAs(User::factory()->create())->getJson('/books/isbn/'.self::ISBN);

        $response->assertNotFound();
        $response->assertExactJson(['error' => '該当する書籍が見つかりませんでした。']);
    }

    /**
     * 14-1-7 Http::fake()でAPI呼び出し自体が失敗した場合、502エラーが返る
     */
    public function test_returns_502_when_api_call_fails(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'server error'], 500),
        ]);

        $response = $this->actingAs(User::factory()->create())->getJson('/books/isbn/'.self::ISBN);

        $response->assertStatus(502);
        $response->assertExactJson(['error' => '書籍情報の取得中にエラーが発生しました。']);
    }
}
