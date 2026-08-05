<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = '数据总览';

    protected static ?string $navigationLabel = '数据总览';

    public function getMaxContentWidth(): string | \Filament\Support\Enums\Width | null
    {
        return 'full';
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverview::class,
            \App\Filament\Widgets\TrafficOverview::class,
            \App\Filament\Widgets\OrdersRevenueChart::class,
            \App\Filament\Widgets\UserRegistrationsChart::class,
            \App\Filament\Widgets\TopProductsChart::class,
            \App\Filament\Widgets\OrderStatusChart::class,
            \App\Filament\Widgets\PaymentHealthChart::class,
            \App\Filament\Widgets\DiscountPerformanceChart::class,
            \App\Filament\Widgets\AffiliatePerformanceChart::class,
            \App\Filament\Widgets\SupportWorkloadChart::class,
        ];
    }
}
