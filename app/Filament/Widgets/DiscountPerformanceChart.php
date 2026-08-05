<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DiscountPerformanceChart extends ChartWidget
{
    protected ?string $heading = '折扣码使用效果分析';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $performance = DB::table('discount_redemptions')
            ->select('code', DB::raw('COUNT(*) as redemption_count'), DB::raw('SUM(discount_amount) as total_discount_amount'))
            ->groupBy('code')
            ->orderByDesc('redemption_count')
            ->limit(10)
            ->get();

        $labels = [];
        $counts = [];
        $amounts = [];

        foreach ($performance as $item) {
            $labels[] = $item->code;
            $counts[] = (int) $item->redemption_count;
            $amounts[] = round((float) $item->total_discount_amount, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => '使用次数 (次)',
                    'data' => $counts,
                    'backgroundColor' => '#f59e0b',
                ],
                [
                    'label' => '优惠总额 ($)',
                    'data' => $amounts,
                    'backgroundColor' => '#ef4444',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
