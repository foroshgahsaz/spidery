<?php

namespace App\Http\Controllers;

use App\Services\Media\DisplayImageService;
use App\Services\Media\HomepageImageService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ThumbnailController extends Controller
{
    public function __invoke(string $section, string $path, DisplayImageService $display, HomepageImageService $homepageImages): BinaryFileResponse
    {
        if (! array_key_exists($section, $homepageImages->definitions())) {
            abort(404);
        }

        $response = $display->render($section, $path);

        if ($response === null) {
            abort(404);
        }

        return $response;
    }
}
