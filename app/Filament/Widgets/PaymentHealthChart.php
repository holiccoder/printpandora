<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PaymentHealthChart extends ChartWidget
{
    protected ?string $heading = '支付健康度与渠道分布';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $payments = Order::select('payment_method', 'payment_status', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method', 'payment_status')
            ->get();

        $methods = $payments->pluck('payment_method')->unique()->filter()->values()->toArray();
        if (empty($methods)) {
            $methods = ['manual', 'paypal', 'cryptomus'];
        }

        $statuses = [
            'paid' => '已付款',
            'pending' => '未付款',
            'failed' => '已失败',
        ];

        $colors = [
            'paid' => '#10b981',
            'pending' => '#f59e0b',
            'failed' => '#ef4444',
        ];

        $methodLabels = array_map(fn($m) => match($m) {
            'manual' => '人工/线下支付',
            'paypal' => 'PayPal',
            'cryptomus' => 'Cryptomus (加密货币)',
            default => ucfirst($m),
        }, $methods);

        $datasets = [];
        foreach ($statuses as $statusKey => $statusLabel) {
            $statusData = [];
            foreach ($methods as $method) {
                $match = $payments->first(fn($p) => $p->payment_method === $method && $p->payment_status === $statusKey);
                $statusData[] = $match ? (int)$match->count : 0;
            }
            $datasets[] = [
                'label' => $statusLabel,
                'data' => $statusData,
                'backgroundColor' => $colors[$statusKey],
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $methodLabels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                ],
            ],
        ];
    }
}
