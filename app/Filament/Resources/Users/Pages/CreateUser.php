<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // اگر نقش admin است و photo خالی است، مقدار پیش‌فرض را تنظیم کن
        if (isset($data['role']) && $data['role'] === 'admin' && empty($data['photo'])) {
            $data['photo'] = 'default-user.jpg';
        }

        return $data;
    }
}
