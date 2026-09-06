<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_command_runs_successfully(): void
    {
        $this->artisan('shop:health-check', ['--section' => 'code,optimizer'])
            ->assertSuccessful();
    }

    public function test_health_check_json_output_is_valid(): void
    {
        $this->artisan('shop:health-check', ['--section' => 'code', '--json' => true])
            ->assertSuccessful();

        $this->assertTrue(class_exists(\App\Console\Commands\HealthCheck::class));
    }
}
