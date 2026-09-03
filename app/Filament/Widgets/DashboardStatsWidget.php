<?php

namespace App\Filament\Widgets;

use App\Services\ReportingService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $reporting = app(ReportingService::class);

        return [
            Stat::make('Revenue', $reporting->totalRevenue()->format())
                ->description('Paid, shipped & delivered orders')
                ->color('success'),
            Stat::make('Profit', $reporting->totalProfit()->format())
                ->description('Revenue − snapshotted cost')
                ->color('success'),
            Stat::make('Orders', (string) $reporting->totalOrders())
                ->description('With recognised revenue'),
            Stat::make('Units in inventory', (string) $reporting->unitsInInventory())
                ->description('On-hand stock across the catalogue'),
        ];
    }
}
