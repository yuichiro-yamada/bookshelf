<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // public/build に Vite のマニフェストが存在しない状態でも
        // @vite ディレクティブを含むビューがエラーにならないようにする
        $this->withoutVite();
    }
}
