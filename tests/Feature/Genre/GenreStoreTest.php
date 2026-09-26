<?php

namespace Tests\Feature\Genre;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ジャンル登録機能のテスト（テストケース一覧「ジャンル登録」に対応）
 */
class GenreStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 5-2-1 未ログインの場合、ジャンル登録ページにアクセスできない
     */
    public function test_guest_cannot_access_genre_create_page(): void
    {
        $response = $this->get(route('genres.create'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 5-2-2 ジャンル名が入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), ['name' => '']);

        $response->assertSessionHasErrors(['name' => 'ジャンル名を入力してください']);
    }

    /**
     * 5-2-3 ジャンル名が21文字以上の場合、バリデーションメッセージが表示される
     */
    public function test_name_must_not_exceed_20_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), ['name' => str_repeat('あ', 21)]);

        $response->assertSessionHasErrors(['name' => 'ジャンル名は20文字以内で入力してください']);
    }

    /**
     * 5-2-4 既に登録されているジャンル名の場合、バリデーションメッセージが表示される
     */
    public function test_name_must_be_unique(): void
    {
        $user = User::factory()->create();
        Genre::factory()->create(['name' => 'ミステリー']);

        $response = $this->actingAs($user)->post(route('genres.store'), ['name' => 'ミステリー']);

        $response->assertSessionHasErrors(['name' => 'このジャンル名はすでに登録されています']);
    }

    /**
     * 5-2-5 正しくジャンル名が入力されている場合、ジャンルが登録される
     */
    public function test_genre_is_registered_with_valid_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), ['name' => 'SF']);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを登録しました。');
        $this->assertDatabaseHas('genres', ['name' => 'SF']);

        $this->get(route('genres.index'))->assertSee('ジャンルを登録しました。');
    }
}
