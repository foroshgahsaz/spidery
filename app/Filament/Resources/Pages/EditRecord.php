<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\FileUploadSanitizer;
use Filament\Notifications\Notification;

abstract class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected function getSavedNotification(): ?Notification
    {
        return CrudSuccessNotification::saved();
    }

    protected function beforeValidate(): void
    {
        FileUploadSanitizer::sanitize($this, $this->form, $this->getRecord());
    }
}
