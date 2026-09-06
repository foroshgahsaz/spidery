<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogLivewireUpload
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('livewire.upload.incoming', [
            'method' => $request->method(),
            'host' => $request->getHost(),
            'scheme' => $request->getScheme(),
            'secure' => $request->isSecure(),
            'has_session' => $request->hasSession(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'has_csrf' => $request->hasHeader('X-CSRF-TOKEN') || $request->has('_token'),
            'content_length' => $request->header('Content-Length'),
            'file_keys' => array_keys($request->allFiles()),
            'php_user' => function_exists('posix_getpwuid')
                ? (posix_getpwuid(posix_geteuid())['name'] ?? null)
                : get_current_user(),
        ]);

        $response = $next($request);

        Log::info('livewire.upload.outgoing', [
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
