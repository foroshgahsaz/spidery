<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class SeoPresenter
{
    public function __construct(
        protected ?Model $model = null,
        protected ?string $title = null,
        protected ?string $description = null,
        protected ?string $image = null,
        protected ?string $url = null,
        protected ?string $type = 'website',
    ) {}

    public static function for(?Model $model = null, array $overrides = []): self
    {
        $site = app(\App\Services\Settings\SettingsService::class)->site();
        $siteName = $site['name'] ?? config('app.name', 'چاپینو');

        $title = $overrides['title'] ?? null;
        $description = $overrides['description'] ?? null;
        $image = $overrides['image'] ?? null;
        $url = $overrides['url'] ?? url()->current();
        $type = $overrides['type'] ?? 'website';

        if ($model) {
            $title = $title ?: ($model->meta_title ?? $model->title ?? $model->name ?? $siteName);
            $description = $description ?: ($model->meta_description ?? $model->short_description ?? $model->excerpt ?? $site['description'] ?? null);
            $image = $image ?: self::resolveImage($model);
            $url = $model->canonical_url ?? $url;
        }

        if (! $title) {
            $title = $siteName;
        }

        if (! $description) {
            $description = $site['description'] ?? 'فروشگاه آنلاین';
        }

        return new self($model, $title, $description, $image, $url, $type);
    }

    public function title(): string
    {
        return $this->title ?? config('app.name');
    }

    public function description(): string
    {
        return $this->description ?? '';
    }

    public function image(): ?string
    {
        return $this->image;
    }

    public function url(): string
    {
        return $this->url ?? url()->current();
    }

    public function type(): string
    {
        return $this->type;
    }

    public function robots(): string
    {
        return $this->model?->robots ?? 'index,follow';
    }

    public function ogTitle(): string
    {
        return $this->model?->og_title ?? $this->title();
    }

    public function ogDescription(): string
    {
        return $this->model?->og_description ?? $this->description();
    }

    protected static function resolveImage(Model $model): ?string
    {
        if (! empty($model->og_image)) {
            return ShopMedia::url($model->og_image);
        }

        if (method_exists($model, 'images') && $model->relationLoaded('images') && $model->images->isNotEmpty()) {
            return ShopMedia::url($model->images->first()->path);
        }

        if (! empty($model->image)) {
            return ShopMedia::url($model->image);
        }

        if (! empty($model->featured_image)) {
            return ShopMedia::url($model->featured_image);
        }

        return null;
    }
}
