<?php

namespace Tests\Feature\Genre;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ジャンル編集機能のテスト（テストケース一覧「ジャンル編集」に対応）
 */
class GenreUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 5-4-1 未ログインの場合、ジャンル編集ページにアクセスできない
     */
    public function test_guest_cannot_access_genre_edit_page(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->get(route('genres.edit', $genre));

        $response->assertRedirect(route('login'));
    }

    /**
     * 5-4-2 編集画面を開くと現在のジャンル名が初期表示される
     */
    public function test_edit_page_shows_current_genre_name(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '編集前ジャンル']);

        $response = $this->actingAs($user)->get(route('genres.edit', $genre));

        $response->assertOk();
        $response->assertSee('value="編集前ジャンル"', false);
    }

    /**
     * 5-4-3 ジャンル名が入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_name_is_required(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->put(route('genres.update', $genre), ['name' => '']);

        $response->assertSessionHasErrors(['name' => 'ジャンル名を入力してください']);
    }

    /**
     * 5-4-4 既に登録されている他のジャンル名に変更しようとした場合、バリデーションメッセージが表示される
     */
    public function test_name_must_be_unique_against_other_genres(): void
    {
        $user = User::factory()->create();
        Genre::factory()->create(['name' => '既存ジャンル']);
        $genre = Genre::factory()->create(['name' => '変更前ジャンル']);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), ['name' => '既存ジャンル']);

        $response->assertSessionHasErrors(['name' => 'このジャンル名はすでに登録されています']);
    }

    /**
     * 5-4-5 正しくジャンル名が入力されている場合、ジャンル情報が更新される
     */
    public function test_genre_is_updated_with_valid_name(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '変更前']);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), ['name' => '変更後']);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを更新しました。');
        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '変更後']);

        $this->get(route('genres.index'))->assertSee('ジャンルを更新しました。');
    }
}
