<?php

namespace App\Services\Media;

use App\Services\Settings\SettingsService;

class HomepageImageService
{
    public const SETTINGS_GROUP = 'homepage_images';

    public function __construct(
        protected SettingsService $settings,
    ) {}

    /** @return array<string, array<string, mixed>> */
    public function definitions(): array
    {
        return config('homepage-images.sections', []);
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        try {
            $stored = $this->settings->get(self::SETTINGS_GROUP, 'sections');
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
    public function forSection(string $section): ?array
    {
        $preset = $this->all()[$section] ?? null;

        return is_array($preset) ? $preset : null;
    }

    public function isEnabled(string $section): bool
    {
        $preset = $this->forSection($section);

        return (bool) ($preset['enabled'] ?? true);
    }

    /** @return array<int, array<string, mixed>> */
    public function forAdminForm(): array
    {
        return collect($this->all())
            ->map(function (array $preset, string $key): array {
                return [
                    'key' => $key,
                    'label' => (string) ($preset['label'] ?? $key),
                    'enabled' => (bool) ($preset['enabled'] ?? true),
                    'mode' => (string) ($preset['mode'] ?? 'fit'),
                    'width' => (int) ($preset['width'] ?? 400),
                    'height' => (int) ($preset['height'] ?? 400),
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
        $sections = [];

        foreach ($rows as $row) {
            $key = (string) ($row['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $defaults = $this->definitions()[$key] ?? [];
            $mode = in_array(($row['mode'] ?? 'fit'), ['fit', 'contain', 'cover'], true)
                ? (string) $row['mode']
                : 'fit';
            $format = in_array(($row['format'] ?? 'webp'), ['webp', 'jpg', 'jpeg', 'png'], true)
                ? (string) $row['format']
                : 'webp';

            $sections[$key] = array_merge($defaults, [
                'label' => (string) ($defaults['label'] ?? $key),
                'enabled' => (bool) ($row['enabled'] ?? true),
                'mode' => $mode,
                'width' => max(16, (int) ($row['width'] ?? 400)),
                'height' => max(16, (int) ($row['height'] ?? 400)),
                'quality' => min(100, max(40, (int) ($row['quality'] ?? 85))),
                'format' => $format,
            ]);
        }

        $this->settings->set(self::SETTINGS_GROUP, 'sections', json_encode(
            $this->mergeWithDefaults($sections),
            JSON_UNESCAPED_UNICODE
        ));
    }

    /** @return array<string, mixed> */
    public function optimizerPreset(string $section): array
    {
        $preset = $this->forSection($section) ?? [];
        $mode = (string) ($preset['mode'] ?? 'fit');
        $width = max(1, (int) ($preset['width'] ?? 400));
        $height = max(1, (int) ($preset['height'] ?? 400));
        $quality = (int) ($preset['quality'] ?? 85);
        $format = (string) ($preset['format'] ?? 'webp');

        if ($mode === 'cover') {
            return [
                'cover_width' => $width,
                'cover_height' => $height,
                'quality' => $quality,
                'format' => $format,
            ];
        }

        return [
            'max_width' => $width,
            'max_height' => $height,
            'quality' => $quality,
            'format' => $format,
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
