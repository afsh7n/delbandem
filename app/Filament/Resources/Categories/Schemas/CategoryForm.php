<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('عنوان')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('عنوان دسته‌بندی را وارد کنید'),
                Textarea::make('description')
                    ->label('توضیحات')
                    ->rows(3)
                    ->placeholder('توضیحات دسته‌بندی را وارد کنید'),
            ]);
    }
}
