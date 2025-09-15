<?php

namespace App\Filament\Resources\Headers\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class HeaderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('images')
                    ->label('تصاویر')
                    ->multiple()
                    ->image()
                    ->directory('headers')
                    ->required()
                    ->helperText('تصاویر هدر سایت را آپلود کنید'),
            ]);
    }
}
