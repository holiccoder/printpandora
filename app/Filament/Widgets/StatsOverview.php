<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\DiscountCode;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $paidOrdersQuery = Order::where('payment_status', 'paid');
        $totalRevenue = (float) $paidOrdersQuery->sum('total');
        $paidOrdersCount = $paidOrdersQuery->count();
        $aov = $paidOrdersCount > 0 ? ($totalRevenue / $paidOrdersCount) : 0;

        $pendingOrdersCount = Order::where('status', 'pending')->count();

        return [
            Stat::make('总收益', '$' . number_format($totalRevenue, 2))
                ->description('已支付订单总金额')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
            Stat::make('已付款订单', number_format($paidOrdersCount))
                ->description('已完成付款的订单总数')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),
            Stat::make('平均订单价值 (AOV)', '$' . number_format($aov, 2))
                ->description('每笔已支付订单的平均金额')
                ->descriptionIcon('heroicon-m-presentation-chart-line')
                ->color('info'),
            Stat::make('待付款订单', number_format($pendingOrdersCount))
                ->description('当前状态为待付款的订单')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
