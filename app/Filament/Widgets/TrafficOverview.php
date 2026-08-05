<?php

namespace App\Filament\Widgets;

use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class TrafficOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        try {
            // Query today's traffic
            $todayPeriod = Period::create(Carbon::today(), Carbon::today());
            $todayData = Analytics::fetchVisitorsAndPageViews($todayPeriod);
            $todayUsers = (int) $todayData->sum('activeUsers');
            $todayViews = (int) $todayData->sum('screenPageViews');

            // Query yesterday's traffic
            $yesterdayPeriod = Period::create(Carbon::yesterday(), Carbon::yesterday());
            $yesterdayData = Analytics::fetchVisitorsAndPageViews($yesterdayPeriod);
            $yesterdayUsers = (int) $yesterdayData->sum('activeUsers');
            $yesterdayViews = (int) $yesterdayData->sum('screenPageViews');

            // Query previous month's traffic
            $prevMonthStart = Carbon::now()->subMonth()->startOfMonth();
            $prevMonthEnd = Carbon::now()->subMonth()->endOfMonth();
            $prevMonthPeriod = Period::create($prevMonthStart, $prevMonthEnd);
            $prevMonthData = Analytics::fetchVisitorsAndPageViews($prevMonthPeriod);
            $prevMonthUsers = (int) $prevMonthData->sum('activeUsers');
            $prevMonthViews = (int) $prevMonthData->sum('screenPageViews');

            $isMock = false;
        } catch (\Exception $e) {
            // Graceful fallback to simulated high-fidelity data if GA4 credentials are not yet set up
            $todayUsers = 342;
            $todayViews = 1120;

            $yesterdayUsers = 295;
            $yesterdayViews = 980;

            $previousMonthUsers = 8450;
            $previousMonthViews = 27800;

            $isMock = true;
        }

        $mockSuffix = $isMock ? ' (演示数据)' : '';

        return [
            Stat::make('今日网站流量' . $mockSuffix, number_format($todayUsers) . ' 访客 / ' . number_format($todayViews) . ' 浏览')
                ->description($isMock ? '请在 .env 中配置 Google Analytics 凭证' : '今日网站实时访客与页面浏览量')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('success'),
            Stat::make('昨日网站流量' . $mockSuffix, number_format($yesterdayUsers) . ' 访客 / ' . number_format($yesterdayViews) . ' 浏览')
                ->description($isMock ? '请在 .env 中配置 Google Analytics 凭证' : '昨日全天总访客与页面浏览量')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),
            Stat::make('上月网站总流量' . $mockSuffix, number_format($previousMonthUsers) . ' 访客 / ' . number_format($previousMonthViews) . ' 浏览')
                ->description($isMock ? '请在 .env 中配置 Google Analytics 凭证' : Carbon::now()->subMonth()->translatedFormat('Y年n月') . ' 全月总流量统计')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
        ];
    }
}
