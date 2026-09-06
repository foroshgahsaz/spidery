<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogLivewireUploadTest extends TestCase
{
    public function test_it_logs_livewire_upload_requests(): void
    {
        Log::spy();

        $response = $this->post('/livewire/upload-file', [], [
            'Accept' => 'application/json',
        ]);

        Log::shouldHaveReceived('info')
            ->with('livewire.upload.incoming', \Mockery::type('array'))
            ->once();

        Log::shouldHaveReceived('info')
            ->with('livewire.upload.outgoing', \Mockery::type('array'))
            ->once();

        $this->assertNotSame(404, $response->getStatusCode());
    }
}
