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

    public function test_guest_cannot_access_genre_edit_page(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->get(route('genres.edit', $genre));

        $response->assertRedirect(route('login'));
    }

    public function test_edit_page_shows_current_genre_name(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '編集前ジャンル']);

        $response = $this->actingAs($user)->get(route('genres.edit', $genre));

        $response->assertOk();
        $response->assertSee('編集前ジャンル');
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->put(route('genres.update', $genre), ['name' => '']);

        $response->assertSessionHasErrors(['name' => 'ジャンル名を入力してください']);
    }

    public function test_name_must_be_unique_against_other_genres(): void
    {
        $user = User::factory()->create();
        Genre::factory()->create(['name' => '既存ジャンル']);
        $genre = Genre::factory()->create(['name' => '変更前ジャンル']);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), ['name' => '既存ジャンル']);

        $response->assertSessionHasErrors(['name' => 'このジャンル名はすでに登録されています']);
    }

    public function test_genre_is_updated_with_valid_name(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '変更前']);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), ['name' => '変更後']);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを更新しました。');
        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '変更後']);
    }
}
