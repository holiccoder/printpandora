<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class OrderStatusChart extends ChartWidget
{
    protected ?string $heading = '订单状态分布';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $statusCounts = Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statuses = Order::statusOptions();

        $colors = [
            'pending' => '#f59e0b',
            'pending_review' => '#f97316',
            'pending_confirmation' => '#a855f7',
            'production' => '#6366f1',
            'shipped' => '#8b5cf6',
        ];

        $labels = [];
        $data = [];
        $backgroundColor = [];

        foreach ($statuses as $key => $label) {
            $labels[] = $label;
            $data[] = (int) ($statusCounts[$key] ?? 0);
            $backgroundColor[] = $colors[$key] ?? '#6b7280';
        }

        return [
            'datasets' => [
                [
                    'label' => '订单数',
                    'data' => $data,
                    'backgroundColor' => $backgroundColor,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
