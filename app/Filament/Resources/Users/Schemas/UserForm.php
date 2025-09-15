<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('ایمیل')
                    ->email()
                    ->maxLength(30)
                    ->placeholder('example@domain.com'),
                TextInput::make('phone_number')
                    ->label('شماره تلفن')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('09123456789'),
                TextInput::make('google_id')
                    ->label('شناسه گوگل')
                    ->placeholder('Google ID'),
                FileUpload::make('photo')
                    ->label('تصویر پروفایل')
                    ->image()
                    ->directory('users')
                    ->default('default-user.jpg'),
                Select::make('role')
                    ->label('نقش')
                    ->options([
                        'user' => 'کاربر',
                        'admin' => 'مدیر',
                    ])
                    ->default('user'),
                Toggle::make('active')
                    ->label('فعال')
                    ->default(true),
            ]);
    }
}
