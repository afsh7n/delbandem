<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Header;
use App\Models\Notification;
use App\Models\Story;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('دسته‌بندی‌ها', Category::count())
                ->description('تعداد کل دسته‌بندی‌ها')
                ->descriptionIcon('heroicon-m-tag')
                ->color('success'),
            
            Stat::make('داستان‌ها', Story::count())
                ->description('تعداد کل داستان‌ها')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('info'),
            
            Stat::make('اعلان‌ها', Notification::count())
                ->description('تعداد کل اعلان‌ها')
                ->descriptionIcon('heroicon-m-bell')
                ->color('warning'),
            
            Stat::make('هدرها', Header::count())
                ->description('تعداد کل هدرها')
                ->descriptionIcon('heroicon-m-photo')
                ->color('danger'),
        ];
    }
}
