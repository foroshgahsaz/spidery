<?php

namespace App\Models;

use App\Support\ShopMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    protected $fillable = [
        'disk',
        'path',
        'path_hash',
        'folder',
        'mime_type',
        'size',
        'width',
        'height',
        'original_name',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    public function url(): ?string
    {
        return ShopMedia::url($this->path);
    }

    public function existsOnDisk(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    public function dimensionsLabel(): string
    {
        if ($this->width && $this->height) {
            return "{$this->width}×{$this->height}";
        }

        return '—';
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;

        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }

    public static function pathHash(string $disk, string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        return hash('sha256', $disk.'|'.$path);
    }
}
