<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrdersRevenueChart extends ChartWidget
{
    protected ?string $heading = '订单与收益';

    protected ?string $description = '订单数量与销售额趋势';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => '最近 7 天',
            '30' => '最近 30 天',
            '90' => '最近 90 天',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $range = (int) ($this->filter ?? 30);
        $days = collect(range($range - 1, 0))->map(fn (int $i) => Carbon::now()->subDays($i));

        $labels = $days->map(fn (Carbon $date) => $date->translatedFormat('n月j日'));

        $ordersByDay = Order::select(
            DB::raw('DATE(created_at) as day'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total) as revenue')
        )
            ->where('created_at', '>=', Carbon::now()->subDays($range - 1)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $orderCounts = $days->map(fn (Carbon $date) => (int) ($ordersByDay->get($date->format('Y-m-d'))?->count ?? 0));
        $revenues = $days->map(fn (Carbon $date) => round((float) ($ordersByDay->get($date->format('Y-m-d'))?->revenue ?? 0), 2));

        return [
            'datasets' => [
                [
                    'label' => '订单数',
                    'data' => $orderCounts->values()->toArray(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => '收益 ($)',
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
