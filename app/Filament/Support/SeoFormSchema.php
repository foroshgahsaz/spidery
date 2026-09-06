<?php

namespace App\Filament\Support;

use Filament\Forms;

class SeoFormSchema
{
    /** @return array<int, Forms\Components\Component> */
    public static function productSection(): array
    {
        return [
            Forms\Components\Section::make('SEO و شبکه‌های اجتماعی')
                ->icon('heroicon-o-magnifying-glass')
                ->schema(self::baseFields(includeOgText: true))
                ->columns(2)
                ->collapsible(),
        ];
    }

    /** @return array<int, Forms\Components\Component> */
    public static function contentSection(): array
    {
        return [
            Forms\Components\Section::make('SEO و شبکه‌های اجتماعی')
                ->icon('heroicon-o-magnifying-glass')
                ->schema(self::baseFields(includeOgText: false))
                ->columns(2)
                ->collapsible(),
        ];
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function baseFields(bool $includeOgText): array
    {
        $fields = [
            Forms\Components\TextInput::make('meta_title')
                ->label('عنوان متا (Title Tag)')
                ->maxLength(70)
                ->helperText('حداکثر ۶۰–۷۰ کاراکتر'),
            Forms\Components\Textarea::make('meta_description')
                ->label('توضیحات متا')
                ->maxLength(160)
                ->rows(3),
            Forms\Components\TextInput::make('meta_keywords')
                ->label('کلمات کلیدی')
                ->maxLength(255),
        ];

        if ($includeOgText) {
            $fields[] = Forms\Components\TextInput::make('og_title')->label('عنوان Open Graph')->maxLength(70);
            $fields[] = Forms\Components\Textarea::make('og_description')->label('توضیح Open Graph')->maxLength(200)->rows(2);
        }

        $fields[] = ShopMediaPicker::image('og_image', 'seo', 'تصویر Open Graph');
        $fields[] = Forms\Components\TextInput::make('canonical_url')->label('آدرس Canonical')->url();
        $fields[] = Forms\Components\Select::make('robots')
            ->label('دستور Robots')
            ->options([
                'index,follow' => 'Index, Follow',
                'index,nofollow' => 'Index, NoFollow',
                'noindex,follow' => 'NoIndex, Follow',
                'noindex,nofollow' => 'NoIndex, NoFollow',
            ])
            ->default('index,follow');

        return $fields;
    }
}
