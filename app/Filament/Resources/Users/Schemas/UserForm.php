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
                    ->email()
                    ->maxLength(30),
                TextInput::make('phone_number')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('google_id')
                    ->label('Google ID'),
                FileUpload::make('photo')
                    ->image()
                    ->directory('users')
                    ->default('default-user.jpg'),
                Select::make('role')
                    ->options([
                        'user' => 'User',
                        'admin' => 'Admin',
                    ])
                    ->default('user'),
                Toggle::make('active')
                    ->default(true),
            ]);
    }
}
