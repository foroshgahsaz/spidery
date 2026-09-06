@php
    $files = $files ?? collect();
    $directoryLabel = $directoryLabel ?? 'فایل‌ها';
    $statePath = $getStatePath();
    $formRoot = str($statePath)->beforeLast('.')->toString();
    $wireKey = md5($statePath.'|'.$directory);
@endphp

<div
    class="media-library-grid-host"
    x-data="{
        formRoot: @js($formRoot),
    }"
    x-on:media-library-picked.window="
        if ($event.detail.formRoot !== formRoot) return;
        $wire.set(formRoot + '.selected_path', $event.detail.path, false);
        if ($event.detail.altText) $wire.set(formRoot + '.alt_text', $event.detail.altText, false);
        if ($event.detail.title) $wire.set(formRoot + '.title', $event.detail.title, false);
    "
    x-on:media-library-deleted.window="
        if ($event.detail.formRoot !== formRoot) return;
        const current = $wire.get(formRoot + '.selected_path');
        if ($event.detail.paths.includes(current)) {
            $wire.set(formRoot + '.selected_path', null, false);
            $wire.set(formRoot + '.alt_text', null, false);
            $wire.set(formRoot + '.title', null, false);
        }
    "
>
    @livewire(\App\Livewire\Admin\MediaLibraryGrid::class, [
        'directory' => $directory,
        'formRoot' => $formRoot,
        'wireKey' => $wireKey,
    ], key('media-library-'.$wireKey))
</div>
