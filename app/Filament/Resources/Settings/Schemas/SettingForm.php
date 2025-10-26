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
                ->label('Key')
                ->required()
                ->unique(ignoreRecord: true)
                ->disabled(fn ($record) => $record !== null) // Disable editing key for existing records
                ->helperText('Unique identifier for the setting. Cannot be changed after creation.')
                ->maxLength(255),

            Select::make('type')
                ->label('Type')
                ->required()
                ->options([
                    'string' => 'String',
                    'number' => 'Number',
                    'boolean' => 'Boolean',
                    'json' => 'JSON',
                    'text' => 'Text',
                ])
                ->default('string')
                ->live()
                ->helperText('Data type for the setting value'),

            TextInput::make('value')
                ->label('Value')
                ->required()
                ->visible(fn ($get) => in_array($get('type'), ['string', 'number']))
                ->helperText('Setting value'),

            Select::make('value')
                ->label('Value')
                ->required()
                ->options([
                    '1' => 'True',
                    '0' => 'False',
                ])
                ->visible(fn ($get) => $get('type') === 'boolean')
                ->helperText('Boolean value for the setting'),

            Textarea::make('value')
                ->label('Value')
                ->required()
                ->rows(4)
                ->visible(fn ($get) => in_array($get('type'), ['text', 'json']))
                ->helperText(fn ($get) => $get('type') === 'json' ? 'Valid JSON format required' : 'Text value for the setting'),

            TextInput::make('description')
                ->label('Description')
                ->helperText('Optional description for the setting')
                ->maxLength(255),
        ]);
    }
}
