<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // اگر پسورد خالی است، آن را از داده‌ها حذف کن تا به‌روزرسانی نشود
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // اگر نقش admin است و photo در داده‌ها نیست یا خالی است
        if (isset($data['role']) && $data['role'] === 'admin') {
            // اگر photo در داده‌ها نیست، مقدار موجود در رکورد را حفظ کن
            if (!isset($data['photo']) || empty($data['photo'])) {
                $data['photo'] = $this->record->photo ?? 'default-user.jpg';
            }
        }

        // اطمینان حاصل کن که photo هرگز null نباشد
        if (empty($data['photo'])) {
            $data['photo'] = 'default-user.jpg';
        }

        return $data;
    }
}
