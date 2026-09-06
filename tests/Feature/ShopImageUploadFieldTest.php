<?php

namespace Tests\Feature;

use App\Filament\Support\ShopIconUpload;
use App\Filament\Support\ShopMediaPicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

class ShopImageUploadFieldTest extends TestCase
{
    public function test_shop_media_picker_renders_media_center_button(): void
    {
        $html = Livewire::test(ShopImageUploadFormStub::class)->html();

        $this->assertStringContainsString('media-picker-field', $html);
        $this->assertStringContainsString('انتخاب / تغییر تصویر', $html);
        $this->assertStringContainsString('openMediaCenter', $html);
    }

    public function test_shop_icon_upload_uses_native_upload_view(): void
    {
        $this->assertSame(
            'filament.forms.components.shop-image-upload',
            ShopIconUpload::make('icon', 'shipping')->getView(),
        );
    }
}

class ShopImageUploadFormStub extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ShopMediaPicker::image('image', 'products', 'تصویر'),
            ])
            ->statePath('data');
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>{{ $this->form }}</div>
        HTML;
    }
}
