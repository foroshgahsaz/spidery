<?php

namespace App\Filament\Resources\MediaFileResource\Pages;

use App\Filament\Resources\MediaFileResource;
use App\Services\Media\MediaRegistry;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewMediaFile extends ViewRecord
{
    protected static string $resource = MediaFileResource::class;

    protected static ?string $title = 'جزئیات فایل';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('فایل')->schema([
                    Infolists\Components\ImageEntry::make('path')
                        ->label('پیش‌نمایش')
                        ->disk('public')
                        ->height(200),
                    Infolists\Components\TextEntry::make('path')->label('مسیر')->copyable(),
                    Infolists\Components\TextEntry::make('folder')->label('پوشه'),
                    Infolists\Components\TextEntry::make('mime_type')->label('MIME'),
                    Infolists\Components\TextEntry::make('size')->label('حجم')->formatStateUsing(
                        fn ($record) => $record->humanSize()
                    ),
                    Infolists\Components\TextEntry::make('dimensions')
                        ->label('ابعاد')
                        ->state(fn ($record) => $record->dimensionsLabel()),
                    Infolists\Components\TextEntry::make('url')
                        ->label('لینک')
                        ->state(fn ($record) => $record->url())
                        ->url(fn ($record) => $record->url())
                        ->openUrlInNewTab(),
                ])->columns(2),
                Infolists\Components\Section::make('محل‌های استفاده')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('usages')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('label')
                                    ->label('محتوا')
                                    ->state(fn ($record) => app(MediaRegistry::class)->usageLabel($record)),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->usages()->exists()),
            ]);
    }
}
