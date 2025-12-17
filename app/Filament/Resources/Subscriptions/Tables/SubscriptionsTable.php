<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Models\Subscription;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('user.phone_number')
                    ->label('کاربر')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('plan.name')
                    ->label('پلن')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Subscription::getStatuses()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Subscription::STATUS_PENDING => 'warning',
                        Subscription::STATUS_ACTIVE => 'success',
                        Subscription::STATUS_EXPIRED => 'danger',
                        Subscription::STATUS_CANCELLED => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('paid_price')
                    ->label('مبلغ پرداختی')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state) . ' تومان' : '-')
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('تاریخ شروع')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('تاریخ پایان')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('ref_id')
                    ->label('کد پیگیری')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(Subscription::getStatuses()),

                SelectFilter::make('plan_id')
                    ->label('پلن')
                    ->relationship('plan', 'name'),

                SelectFilter::make('active')
                    ->label('فقط اشتراک‌های فعال')
                    ->query(fn ($query) => $query->active()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('مشاهده'),
                EditAction::make()
                    ->label('ویرایش'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

