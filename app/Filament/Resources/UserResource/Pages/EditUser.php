<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use App\Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getHeading(): string | Htmlable
    {
        $record = $this->getRecord();

        if ($record instanceof \App\Models\User && auth()->id() === $record->getKey()) {
            return 'مدیریت پروفایل';
        }

        return parent::getHeading();
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        if ($record instanceof \App\Models\User && auth()->id() === $record->getKey()) {
            return 'اطلاعات حساب، تصویر پروفایل و دسترسی‌های شما';
        }

        return parent::getSubheading();
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['user_kind'] = (! empty($data['is_admin']) || ! empty($data['is_author']))
            ? 'staff'
            : 'customer';

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_kind'] = $this->form->getState()['user_kind'] ?? 'customer';

        return CreateUser::normalizeUserKind($data);
    }
}
