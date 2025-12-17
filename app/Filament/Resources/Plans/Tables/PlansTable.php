<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('نام پلن')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('duration_days')
                    ->label('مدت زمان')
                    ->sortable()
                    ->suffix(' روز'),

                TextColumn::make('price')
                    ->label('قیمت')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state) . ' تومان'),

                ToggleColumn::make('is_active')
                    ->label('وضعیت'),

                TextColumn::make('subscriptions_count')
                    ->label('تعداد اشتراک')
                    ->counts('subscriptions')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('آخرین بروزرسانی')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('وضعیت')
                    ->options([
                        1 => 'فعال',
                        0 => 'غیرفعال',
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
            ])
            ->defaultSort('created_at', 'desc');
    }
}

