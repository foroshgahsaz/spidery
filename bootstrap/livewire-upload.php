<?php

spl_autoload_register(static function (string $class): bool {
    if ($class !== 'Livewire\\Features\\SupportFileUploads\\TemporaryUploadedFile') {
        return false;
    }

    require __DIR__.'/overrides/Livewire/TemporaryUploadedFile.php';

    return true;
}, prepend: true);
