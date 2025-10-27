<?php

namespace App\Filament\Resources\PushNotifications\Pages;

use App\Filament\Resources\PushNotifications\PushNotificationResource;
use App\Services\OneSignalService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class CreatePushNotification extends CreateRecord
{
    protected static string $resource = PushNotificationResource::class;
    
    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        try {
            $notification = $this->record;
            
            // ارسال نوتیفیکیشن به OneSignal
            $oneSignalService = new OneSignalService();
            
            // تبدیل مسیر تصویر به URL کامل
            $imageUrl = $notification->image ? Storage::url($notification->image) : null;
            
            // ارسال
            $response = $oneSignalService->sendToAll(
                $notification->title,
                $notification->description ?? '',
                [],
                $imageUrl,
                $notification->link
            );
            
            // علامت‌گذاری به عنوان ارسال شده
            $notification->update(['sent' => true]);
            
            Notification::make()
                ->success()
                ->title('نوتیفیکیشن با موفقیت ارسال شد')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('خطا در ارسال نوتیفیکیشن')
                ->body($e->getMessage())
                ->send();
        }
    }
}

