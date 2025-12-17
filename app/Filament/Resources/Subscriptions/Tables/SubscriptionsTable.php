<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Models\Subscription;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class SubscriptionsTable
{
    public static function table(Table $table): Table
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

                BadgeColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn ($state) => Subscription::getStatuses()[$state] ?? $state)
                    ->colors([
                        'warning' => Subscription::STATUS_PENDING,
                        'success' => Subscription::STATUS_ACTIVE,
                        'danger' => Subscription::STATUS_EXPIRED,
                        'gray' => Subscription::STATUS_CANCELLED,
                    ]),

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
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(Subscription::getStatuses()),

                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('پلن')
                    ->relationship('plan', 'name'),

                Tables\Filters\Filter::make('active')
                    ->label('فقط اشتراک‌های فعال')
                    ->query(fn ($query) => $query->active()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

