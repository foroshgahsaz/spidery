<?php

namespace App\Filament\Support;

trait NormalizesFileUploadFormState
{
    public function getFormUploadedFiles(string $statePath): ?array
    {
        FileUploadStateNormalizer::normalizeStatePath($this, $statePath);

        return parent::getFormUploadedFiles($statePath);
    }
}
