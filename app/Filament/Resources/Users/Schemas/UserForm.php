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
                TextInput::make('name')
                    ->label('نام')
                    ->maxLength(255)
                    ->placeholder('نام کاربر')
                    ->visible(fn ($get) => $get('role') === 'admin'),
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
                Select::make('role')
                    ->label('نقش')
                    ->options([
                        'user' => 'کاربر',
                        'admin' => 'مدیر',
                    ])
                    ->default('user')
                    ->reactive()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        // اگر نقش به admin تغییر کرد و photo خالی است، مقدار پیش‌فرض را تنظیم کن
                        if ($state === 'admin' && empty($get('photo'))) {
                            $set('photo', 'default-user.jpg');
                        }
                    }),
                FileUpload::make('photo')
                    ->label('تصویر پروفایل')
                    ->image()
                    ->disk('public')
                    ->directory('users')
                    ->visibility('public')
                    ->default('default-user.jpg')
                    ->visible(fn ($get) => $get('role') === 'user')
                    ->dehydrated(true),
                TextInput::make('password')
                    ->label('رمز عبور')
                    ->password()
                    ->required(fn ($get, $livewire) => $get('role') === 'admin' && $livewire instanceof \App\Filament\Resources\Users\Pages\CreateUser)
                    ->visible(fn ($get) => $get('role') === 'admin')
                    ->dehydrated(fn ($get, $state) => !empty($state))
                    ->helperText(fn ($livewire) => $livewire instanceof \App\Filament\Resources\Users\Pages\EditUser 
                        ? 'فقط در صورت تغییر رمز عبور، فیلد را پر کنید.' 
                        : 'رمز عبور برای نقش مدیر الزامی است.')
                    ->minLength(8)
                    ->autocomplete('new-password'),
                Toggle::make('active')
                    ->label('فعال')
                    ->default(true),
            ]);
    }
}
