<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalPriceProductToday extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected function getStats(): array
    {
        return [
            Stat::make('Sum Last 7 Days (DZD)',
            number_format(Product::where('created_at', '>=', now()->subDays(7)->startOfDay())->sum('price') / 100, 2))
        ];
    }
}
