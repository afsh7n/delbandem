<?php

namespace App\Filament\Resources\Stories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('author')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->rows(3),
                Select::make('category_id')
                    ->label('Category')
                    ->options(Category::all()->pluck('title', 'id'))
                    ->required(),
                FileUpload::make('image_file_name')
                    ->label('Image')
                    ->image()
                    ->directory('stories/images'),
                FileUpload::make('voice_file_name')
                    ->label('Voice File')
                    ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav'])
                    ->directory('stories/voices'),
                TextInput::make('rate')
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0)
                    ->maxValue(5)
                    ->default(0),
                TextInput::make('total_rates')
                    ->numeric()
                    ->default(1),
            ]);
    }
}
