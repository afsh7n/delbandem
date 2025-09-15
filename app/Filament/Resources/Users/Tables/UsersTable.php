<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->label('تصویر')
                    ->circular()
                    ->defaultImageUrl(url('storage/default-user.jpg')),
                TextColumn::make('phone_number')
                    ->label('شماره تلفن')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('نقش')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'مدیر',
                        'user' => 'کاربر',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'user' => 'success',
                    }),
                ToggleColumn::make('active')
                    ->label('فعال'),
                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('نقش')
                    ->options([
                        'user' => 'کاربر',
                        'admin' => 'مدیر',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('ویرایش'),
                DeleteAction::make()
                    ->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
