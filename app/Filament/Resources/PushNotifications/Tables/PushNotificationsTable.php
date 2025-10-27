<?php

namespace App\Filament\Resources\PushNotifications\Tables;

use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PushNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('تصویر')
                    ->height(50)
                    ->circular(),
                    
                TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('description')
                    ->label('توضیحات')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                    
                BooleanColumn::make('sent')
                    ->label('ارسال شده')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                TextColumn::make('link')
                    ->label('لینک')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (!$state || strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),
                    
                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->bulkActions([]);
    }
}

