<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'داشبورد';
    
    protected static ?string $navigationLabel = 'داشبورد';
    
    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            AccountWidget::class,
        ];
    }
    
    public function getColumns(): int | array
    {
        return 2;
    }
}
