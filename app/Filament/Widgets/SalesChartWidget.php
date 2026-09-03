<?php

namespace App\Filament\Widgets;

use App\Services\ReportingService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Sales over time (last 30 days)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $series = app(ReportingService::class)->salesOverTime(30);

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => array_map(fn (int $cents) => round($cents / 100, 2), array_values($series)),
                    'borderColor' => '#475569',
                    'backgroundColor' => 'rgba(71, 85, 105, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => array_map(
                fn (string $date) => Carbon::parse($date)->format('M j'),
                array_keys($series),
            ),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
