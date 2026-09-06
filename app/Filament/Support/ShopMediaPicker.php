<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;

class ShopMediaPicker
{
    public static function image(string $name, string $directory, string $label): FileUpload
    {
        return FileUpload::make($name)
            ->view('filament.forms.components.shop-image-upload')
            ->label($label)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->maxSize(51200)
            ->imagePreviewHeight('150')
            ->helperText('فایل را انتخاب کنید، تا پایان نوار پیشرفت صبر کنید، بعد ذخیره کنید.')
            ->validationMessages([
                'required' => 'انتخاب تصویر الزامی است.',
                'uploaded' => 'فایل آپلود نشد. دوباره انتخاب کنید و تا پایان آپلود صبر کنید.',
            ]);
    }
}
