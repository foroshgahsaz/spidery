<?php

namespace App\Filament\Support;

use Filament\Notifications\Notification;

class CrudErrorNotification
{
    public static function failed(?string $body = null): Notification
    {
        return Notification::make()
            ->danger()
            ->title('خطا')
            ->body($body ?? 'عملیات انجام نشد. دوباره تلاش کنید.')
            ->icon('heroicon-o-x-circle');
    }

    public static function validation(?string $body = null): Notification
    {
        return Notification::make()
            ->warning()
            ->title('بررسی کنید')
            ->body($body ?? 'برخی فیلدها نامعتبر هستند.')
            ->icon('heroicon-o-exclamation-triangle');
    }
}
