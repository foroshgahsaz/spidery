@php
    $state = $getState();
    $currentPath = collect(is_array($state) ? $state : [])
        ->first(fn ($value) => is_string($value) && $value !== '');
    $previewUrl = $currentPath ? \App\Support\ShopMedia::url($currentPath) : null;
    $openAction = $getAction('openMediaCenter');
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    label-tag="div"
>
    <div class="media-picker-field">
        <div class="media-picker-field__preview">
            @if ($previewUrl)
                <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="media-picker-field__thumb">
                    <img src="{{ $previewUrl }}" alt="">
                </a>
            @else
                <div class="media-picker-field__empty">
                    <x-filament::icon icon="heroicon-o-photo" class="h-7 w-7" />
                    <span>بدون تصویر</span>
                </div>
            @endif
        </div>

        <div class="media-picker-field__actions">
            @if ($openAction)
                {{ $openAction }}
            @endif

            @if ($currentPath)
                <x-filament::button
                    type="button"
                    color="danger"
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
