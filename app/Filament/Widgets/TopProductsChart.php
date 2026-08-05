<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopProductsChart extends ChartWidget
{
    protected ?string $heading = '畅销产品与分类排行';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->select(
                'products.name as product_name',
                'product_categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'product_categories.name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        $labels = [];
        $revenues = [];
        $quantities = [];

        foreach ($topProducts as $item) {
            $labels[] = $item->product_name . ' (' . $item->category_name . ')';
            $revenues[] = round((float) $item->total_revenue, 2);
            $quantities[] = (int) $item->quantity_sold;
        }

        return [
            'datasets' => [
                [
                    'label' => '销售额 ($)',
                    'data' => $revenues,
                    'backgroundColor' => '#10b981',
                ],
                [
                    'label' => '销量',
                    'data' => $quantities,
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }
}
