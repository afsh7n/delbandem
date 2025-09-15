<?php

namespace App\Filament\Resources\Notifications\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class NotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('message')
                    ->label('پیام')
                    ->required()
                    ->rows(4)
                    ->placeholder('متن اعلان را وارد کنید'),
            ]);
    }
}
