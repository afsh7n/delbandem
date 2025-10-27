<?php

namespace App\Filament\Resources\PushNotifications\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class PushNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('عنوان')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('عنوان نوتیفیکیشن را وارد کنید'),
                
                Textarea::make('description')
                    ->label('توضیحات')
                    ->rows(3)
                    ->placeholder('توضیحات نوتیفیکیشن را وارد کنید'),
                
                FileUpload::make('image')
                    ->label('تصویر')
                    ->image()
                    ->directory('push-notifications')
                    ->imageEditor()
                    ->columnSpanFull(),
                
                TextInput::make('link')
                    ->label('لینک')
                    ->url()
                    ->placeholder('آدرس لینک دلخواه (اختیاری)')
                    ->columnSpanFull(),
            ]);
    }
}

