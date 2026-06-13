<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrdersRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Orders & Revenue';

    protected ?string $description = 'Last 7 days';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(fn (int $i) => Carbon::now()->subDays($i));

        $labels = $days->map(fn (Carbon $date) => $date->format('M d'));

        $ordersByDay = Order::select(
            DB::raw('DATE(created_at) as day'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total) as revenue')
        )
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $orderCounts = $days->map(fn (Carbon $date) => (int) ($ordersByDay->get($date->format('Y-m-d'))?->count ?? 0));
        $revenues = $days->map(fn (Carbon $date) => round((float) ($ordersByDay->get($date->format('Y-m-d'))?->revenue ?? 0), 2));

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $orderCounts->values()->toArray(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Revenue ($)',
                    'data' => $revenues->values()->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.3,
                    'yAxisID' => 'revenue',
                ],
            ],
            'labels' => $labels->values()->toArray(),
            'scales' => [
                'revenue' => [
                    'position' => 'right',
                    'grid' => ['display' => false],
                ],
            ],
            'multiple' => true,
        ];
    }
}
