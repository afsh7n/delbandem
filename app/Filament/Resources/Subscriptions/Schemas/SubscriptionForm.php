<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('کاربر')
                    ->required()
                    ->searchable()
                    ->options(
                        User::whereNotNull('phone_number')
                            ->pluck('phone_number', 'id')
                            ->filter()
                    )
                    ->disabled(fn ($context) => $context === 'edit'),

                Select::make('plan_id')
                    ->label('پلن')
                    ->required()
                    ->searchable()
                    ->options(
                        Plan::whereNotNull('name')
                            ->pluck('name', 'id')
                            ->filter()
                    )
                    ->disabled(fn ($context) => $context === 'edit'),

                Select::make('status')
                    ->label('وضعیت')
                    ->required()
                    ->options(Subscription::getStatuses()),

                TextInput::make('start_date')
                    ->label('تاریخ شروع')
                    ->type('datetime-local')
                    ->nullable(),

                TextInput::make('end_date')
                    ->label('تاریخ پایان')
                    ->type('datetime-local')
                    ->nullable(),

                TextInput::make('paid_price')
                    ->label('مبلغ پرداختی (تومان)')
                    ->numeric()
                    ->nullable()
                    ->suffix('تومان'),

                TextInput::make('authority')
                    ->label('Authority')
                    ->maxLength(100)
                    ->nullable()
                    ->disabled(),

                TextInput::make('ref_id')
                    ->label('کد پیگیری')
                    ->maxLength(100)
                    ->nullable()
                    ->disabled(),
            ]);
    }
}

