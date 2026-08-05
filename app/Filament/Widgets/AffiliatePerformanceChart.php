<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AffiliatePerformanceChart extends ChartWidget
{
    protected ?string $heading = '分销商推广业绩排行';

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $performance = DB::table('affiliate_commissions')
            ->join('affiliates', 'affiliate_commissions.affiliate_id', '=', 'affiliates.id')
            ->join('users', 'affiliates.user_id', '=', 'users.id')
            ->join('orders', 'affiliate_commissions.order_id', '=', 'orders.id')
            ->select(
                'users.name as affiliate_name',
                'affiliates.referral_code',
                DB::raw('SUM(affiliate_commissions.amount) as total_commission'),
                DB::raw('SUM(orders.total) as total_referred_revenue')
            )
            ->groupBy('affiliates.id', 'users.name', 'affiliates.referral_code')
            ->orderByDesc('total_referred_revenue')
            ->limit(10)
            ->get();

        $labels = [];
        $commissions = [];
        $revenues = [];

        foreach ($performance as $item) {
            $labels[] = $item->affiliate_name . ' (' . $item->referral_code . ')';
            $commissions[] = round((float) $item->total_commission, 2);
            $revenues[] = round((float) $item->total_referred_revenue, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => '推广订单总额 ($)',
                    'data' => $revenues,
                    'backgroundColor' => '#10b981',
                ],
                [
                    'label' => '佣金支出总额 ($)',
                    'data' => $commissions,
                    'backgroundColor' => '#8b5cf6',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
