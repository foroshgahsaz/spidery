<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\FileUploadSanitizer;
use App\Filament\Support\FileUploadStateNormalizer;
use App\Filament\Support\MissingUploadPathCleaner;
use App\Filament\Support\NormalizesFileUploadFormState;
use Filament\Notifications\Notification;

abstract class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    use NormalizesFileUploadFormState;
    protected function getSavedNotification(): ?Notification
    {
        return CrudSuccessNotification::saved();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return MissingUploadPathCleaner::clearFromFormData(
            parent::mutateFormDataBeforeFill($data),
            $this->getRecord(),
        );
    }

    protected function afterFill(): void
    {
        FileUploadStateNormalizer::normalizeForm($this, $this->form);
        FileUploadSanitizer::sanitize($this, $this->form, $this->getRecord());
    }

    protected function beforeValidate(): void
    {
        FileUploadSanitizer::sanitize($this, $this->form, $this->getRecord());
    }
}
