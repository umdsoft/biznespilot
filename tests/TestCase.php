<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Vite;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Prevent Vite manifest errors in tests
        Vite::useScriptTagAttributes([]);
        Vite::useStyleTagAttributes([]);

        // Mock Vite to avoid manifest.json requirement in tests
        $this->withoutVite();
    }
}
