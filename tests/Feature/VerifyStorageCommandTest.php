<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyStorageCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_storage_command_runs(): void
    {
        $this->artisan('shop:verify-storage')
            ->assertSuccessful();
    }
}
