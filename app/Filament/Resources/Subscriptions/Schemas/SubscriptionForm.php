<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class SubscriptionForm
{
    public static function schema(): array
    {
        return [
            Section::make('اطلاعات اشتراک')
                ->schema([
                    Select::make('user_id')
                        ->label('کاربر')
                        ->required()
                        ->searchable()
                        ->options(User::pluck('phone_number', 'id'))
                        ->disabled(fn ($context) => $context === 'edit'),

                    Select::make('plan_id')
                        ->label('پلن')
                        ->required()
                        ->searchable()
                        ->options(Plan::pluck('name', 'id'))
                        ->disabled(fn ($context) => $context === 'edit'),

                    Select::make('status')
                        ->label('وضعیت')
                        ->required()
                        ->options(Subscription::getStatuses()),

                    DateTimePicker::make('start_date')
                        ->label('تاریخ شروع')
                        ->nullable()
                        ->seconds(false),

                    DateTimePicker::make('end_date')
                        ->label('تاریخ پایان')
                        ->nullable()
                        ->seconds(false),

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
                ])
                ->columns(2),
        ];
    }
}

