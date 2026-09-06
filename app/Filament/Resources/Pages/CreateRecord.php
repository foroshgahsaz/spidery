<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\FileUploadSanitizer;
use Filament\Notifications\Notification;

abstract class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected function getCreatedNotification(): ?Notification
    {
        return CrudSuccessNotification::created();
    }

    protected function beforeValidate(): void
    {
        FileUploadSanitizer::sanitize($this, $this->form);
    }
}
