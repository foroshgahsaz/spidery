<?php

namespace Tests\Unit;

use App\Support\MediaPath;
use Tests\TestCase;

class MediaPathTest extends TestCase
{
    public function test_it_keeps_relative_storage_paths(): void
    {
        $this->assertSame(
            'categories/01M1PA5SE82RSQPM4HCFC4VK8X.webp',
            MediaPath::normalize('categories/01M1PA5SE82RSQPM4HCFC4VK8X.webp'),
        );
    }

    public function test_it_strips_public_url_prefix_from_absolute_urls(): void
    {
        config(['filesystems.disks.public.url' => 'https://sarayechini.ir/data']);

        $this->assertSame(
            'categories/example.webp',
            MediaPath::normalize('https://sarayechini.ir/data/categories/example.webp'),
        );
    }

    public function test_it_strips_leading_data_prefix_from_web_paths(): void
    {
        config(['filesystems.disks.public.url' => 'https://sarayechini.ir/data']);

        $this->assertSame(
            'categories/example.webp',
            MediaPath::normalize('/data/categories/example.webp'),
        );
    }

    public function test_it_strips_storage_symlink_prefix(): void
    {
        $this->assertSame(
            'categories/example.jpg',
            MediaPath::normalize('storage/categories/example.jpg'),
        );
    }
}
