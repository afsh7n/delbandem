<?php

namespace App\Filament\Resources\PushNotifications;

use App\Filament\Resources\PushNotifications\Pages\CreatePushNotification;
use App\Filament\Resources\PushNotifications\Pages\ListPushNotifications;
use App\Filament\Resources\PushNotifications\Schemas\PushNotificationForm;
use App\Filament\Resources\PushNotifications\Tables\PushNotificationsTable;
use App\Models\PushNotification;
use App\Services\OneSignalService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class PushNotificationResource extends Resource
{
    protected static ?string $model = PushNotification::class;

    protected static ?string $modelLabel = 'نوتیفیکیشن پوش';
    
    protected static ?string $pluralModelLabel = 'نوتیفیکیشن‌های پوش';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationGroup = 'مدیریت محتوا';

    public static function form(Schema $schema): Schema
    {
        return PushNotificationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PushNotificationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPushNotifications::route('/'),
            'create' => CreatePushNotification::route('/create'),
        ];
    }

    public static function canEdit(): bool
    {
        return false; // فقط اضافه کردن مجاز است
    }

    public static function canDelete(): bool
    {
        return false; // حذف مجاز نیست
    }
}

