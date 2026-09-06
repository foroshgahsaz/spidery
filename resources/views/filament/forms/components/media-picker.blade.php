@php
    $state = $getState();
    $currentPath = collect(is_array($state) ? $state : [])
        ->first(fn ($value) => is_string($value) && $value !== '');
    $previewUrl = $currentPath ? \App\Support\ShopMedia::url($currentPath) : null;
    $openAction = $getAction('openMediaCenter');
    $mediaFile = $currentPath
        ? \App\Models\MediaFile::query()->where('disk', 'public')->where('path', $currentPath)->first()
        : null;
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    label-tag="div"
>
    <div class="media-picker-field media-picker-field--modern">
        <div class="media-picker-field__card">
            <div class="media-picker-field__preview">
                @if ($previewUrl)
                    <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="media-picker-field__thumb">
                        <img src="{{ $previewUrl }}" alt="{{ $mediaFile?->alt_text ?: '' }}">
                        <span class="media-picker-field__zoom">
                            <x-filament::icon icon="heroicon-m-magnifying-glass-plus" class="h-4 w-4" />
                        </span>
                    </a>
                @else
                    <div class="media-picker-field__empty">
                        <x-filament::icon icon="heroicon-o-photo" class="h-8 w-8" />
                        <span>بدون تصویر</span>
                    </div>
                @endif
            </div>

            <div class="media-picker-field__meta">
                @if ($currentPath)
                    <div class="media-picker-field__path" title="{{ $currentPath }}">
                        {{ \Illuminate\Support\Str::limit($currentPath, 42) }}
                    </div>
                    @if ($mediaFile?->alt_text || $mediaFile?->title)
                        <div class="media-picker-field__seo">
                            @if ($mediaFile?->title)
                                <span><strong>Title:</strong> {{ $mediaFile->title }}</span>
                            @endif
                            @if ($mediaFile?->alt_text)
                                <span><strong>Alt:</strong> {{ \Illuminate\Support\Str::limit($mediaFile->alt_text, 60) }}</span>
                            @endif
                        </div>
                    @endif
                @else
                    <p class="media-picker-field__hint">تصویری انتخاب نشده است.</p>
                @endif
            </div>
        </div>

        <div class="media-picker-field__actions">
            @if ($openAction)
                {{ $openAction }}
            @endif

            @if ($currentPath)
                <x-filament::button
                    type="button"
                    color="gray"
                    outlined
                    icon="heroicon-m-trash"
                    wire:click="$set(@js($getStatePath()), [])"
                >
                    حذف تصویر
                </x-filament::button>
            @endif
        </div>
    </div>
</x-dynamic-component>
