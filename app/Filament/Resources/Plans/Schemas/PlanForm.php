<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام پلن')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('مثال: پلن یک ماهه'),

                TextInput::make('duration_days')
                    ->label('مدت زمان (روز)')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->suffix('روز')
                    ->placeholder('30'),

                TextInput::make('price')
                    ->label('قیمت (تومان)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->suffix('تومان')
                    ->placeholder('100000'),

                Textarea::make('description')
                    ->label('توضیحات')
                    ->rows(3)
                    ->maxLength(1000)
                    ->placeholder('توضیحات پلن...'),

                Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true)
                    ->helperText('پلن‌های غیرفعال برای کاربران نمایش داده نمی‌شوند'),
            ]);
    }
}

