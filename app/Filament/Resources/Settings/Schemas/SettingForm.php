<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')
                ->label('کلید')
                ->required()
                ->unique(ignoreRecord: true)
                ->disabled(fn ($record) => $record !== null) // Disable editing key for existing records
                ->helperText('شناسه یکتا برای تنظیم. پس از ایجاد قابل تغییر نیست.')
                ->maxLength(255),

            Select::make('type')
                ->label('نوع')
                ->required()
                ->options([
                    'string' => 'متن',
                    'number' => 'عدد',
                    'boolean' => 'بولین',
                    'json' => 'JSON',
                    'text' => 'متن طولانی',
                ])
                ->default('string')
                ->live()
                ->helperText('نوع داده برای مقدار تنظیم'),

            TextInput::make('value')
                ->label('مقدار')
                ->required()
                ->visible(fn ($get) => in_array($get('type'), ['string', 'number']))
                ->helperText('مقدار تنظیم'),

            Select::make('value')
                ->label('مقدار')
                ->required()
                ->options([
                    '1' => 'درست',
                    '0' => 'غلط',
                ])
                ->visible(fn ($get) => $get('type') === 'boolean')
                ->helperText('مقدار بولین برای تنظیم'),

            Textarea::make('value')
                ->label('مقدار')
                ->required()
                ->rows(4)
                ->visible(fn ($get) => in_array($get('type'), ['text', 'json']))
                ->helperText(fn ($get) => $get('type') === 'json' ? 'فرمت JSON معتبر الزامی است' : 'مقدار متنی برای تنظیم'),

            TextInput::make('description')
                ->label('توضیحات')
                ->helperText('توضیحات اختیاری برای تنظیم')
                ->maxLength(255),
        ]);
    }
}
