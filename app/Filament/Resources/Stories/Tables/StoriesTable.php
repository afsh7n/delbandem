<?php

namespace App\Filament\Resources\Stories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\Category;

class StoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_file_name')
                    ->label('تصویر')
                    ->square()
                    ->size(60),
                TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('author')
                    ->label('نویسنده')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.title')
                    ->label('دسته‌بندی')
                    ->sortable(),
                TextColumn::make('voice_file_name')
                    ->label('فایل صوتی')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) {
                            return '❌ بدون صدا';
                        }
                        
                        $url = url('storage/' . $state);
                        return new \Illuminate\Support\HtmlString(
                            '<audio controls style="width: 200px; height: 30px;">
                                <source src="' . $url . '" type="audio/mpeg">
                                <source src="' . $url . '" type="audio/wav">
                                مرورگر شما از پخش صدا پشتیبانی نمی‌کند.
                            </audio>'
                        );
                    })
                    ->html(),
                TextColumn::make('rate')
                    ->label('امتیاز')
                    ->badge()
                    ->formatStateUsing(fn ($state) => number_format($state, 1))
                    ->color(fn ($state) => $state >= 4 ? 'success' : ($state >= 2 ? 'warning' : 'danger')),
                TextColumn::make('total_rates')
                    ->label('تعداد امتیازها'),
                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('دسته‌بندی')
                    ->relationship('category', 'title'),
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
