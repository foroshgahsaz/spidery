<?php

namespace App\Services\Media;

use App\Services\Settings\SettingsService;

class MediaPresetService
{
    public const SETTINGS_GROUP = 'media_presets';

    public function __construct(
        protected SettingsService $settings,
    ) {}

    /** @return array<string, array<string, mixed>> */
    public function definitions(): array
    {
        return config('image-optimizer.presets', []);
    }

    /** @return array<string, string> */
    public function directoryMap(): array
    {
        return config('image-optimizer.directory_presets', []);
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        try {
            $stored = $this->settings->get(self::SETTINGS_GROUP, 'presets');
        } catch (\Throwable) {
            return $this->definitions();
        }

        if (is_string($stored) && $stored !== '') {
            $decoded = json_decode($stored, true);

            if (is_array($decoded)) {
                return $this->mergeWithDefaults($decoded);
            }
        }

        return $this->definitions();
    }

    /** @return array<string, mixed>|null */
    public function forDirectory(string $directory): ?array
    {
        $directory = trim($directory, '/');
        $presetKey = $this->directoryMap()[$directory] ?? 'default';

        $preset = $this->all()[$presetKey] ?? null;

        return is_array($preset) ? $preset : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function forAdminForm(): array
    {
        $presets = $this->all();
        $labels = $this->presetLabels();

        return collect($presets)
            ->map(function (array $preset, string $key) use ($labels): array {
                return [
                    'key' => $key,
                    'label' => $labels[$key] ?? $key,
                    'mode' => filled($preset['cover_width'] ?? null) ? 'cover' : 'fit',
                    'max_width' => (int) ($preset['max_width'] ?? $preset['cover_width'] ?? 1600),
                    'max_height' => (int) ($preset['max_height'] ?? $preset['cover_height'] ?? 1600),
                    'quality' => (int) ($preset['quality'] ?? 85),
                    'format' => (string) ($preset['format'] ?? 'webp'),
                ];
            })
            ->values()
            ->all();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    public function saveFromAdminForm(array $rows): void
    {
        $presets = [];

        foreach ($rows as $row) {
            $key = (string) ($row['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $mode = (string) ($row['mode'] ?? 'fit');
            $width = max(1, (int) ($row['max_width'] ?? 1600));
            $height = max(1, (int) ($row['max_height'] ?? 1600));
            $quality = min(100, max(1, (int) ($row['quality'] ?? 85)));
            $format = in_array(($row['format'] ?? 'webp'), ['webp', 'jpg', 'jpeg', 'png'], true)
                ? (string) $row['format']
                : 'webp';

            if ($mode === 'cover') {
                $presets[$key] = [
                    'cover_width' => $width,
                    'cover_height' => $height,
                    'quality' => $quality,
                    'format' => $format,
                ];

                continue;
            }

            $presets[$key] = [
                'max_width' => $width,
                'max_height' => $height,
                'quality' => $quality,
                'format' => $format,
            ];
        }

        $this->settings->set(self::SETTINGS_GROUP, 'presets', json_encode(
            $this->mergeWithDefaults($presets),
            JSON_UNESCAPED_UNICODE
        ));
    }

    /** @return array<string, string> */
    public function presetLabels(): array
    {
        return [
            'product' => 'محصول',
            'logo' => 'لوگو / برند',
            'slider' => 'اسلایدر',
            'category' => 'دسته‌بندی',
            'post' => 'مقاله',
            'seo' => 'تصویر سئو (OG)',
            'avatar' => 'آواتار',
            'settings' => 'تنظیمات سایت',
            'default' => 'پیش‌فرض',
        ];
    }

    /** @param  array<string, array<string, mixed>>  $custom */
    protected function mergeWithDefaults(array $custom): array
    {
        $merged = $this->definitions();

        foreach ($custom as $key => $preset) {
            if (! is_array($preset)) {
                continue;
            }

            $merged[$key] = array_merge($merged[$key] ?? [], $preset);
        }

        return $merged;
    }
}
