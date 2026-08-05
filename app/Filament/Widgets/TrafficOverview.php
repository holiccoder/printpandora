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

            // Query this month's traffic
            $thisMonthStart = Carbon::now()->startOfMonth();
            $thisMonthEnd = Carbon::now();
            $thisMonthPeriod = Period::create($thisMonthStart, $thisMonthEnd);
            $thisMonthData = Analytics::fetchVisitorsAndPageViews($thisMonthPeriod);
            $thisMonthUsers = (int) $thisMonthData->sum('activeUsers');
            $thisMonthViews = (int) $thisMonthData->sum('screenPageViews');

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

            $thisMonthUsers = 5230;
            $thisMonthViews = 17400;

            $previousMonthUsers = 8450;
            $previousMonthViews = 27800;

            $isMock = true;
        }

        $mockSuffix = $isMock ? ' (演示数据)' : '';

        return [
            Stat::make('今日访客' . $mockSuffix, number_format($todayUsers))
                ->description($isMock ? '请配置 Google Analytics 凭证' : '今日页面浏览量 (PV): ' . number_format($todayViews))
                ->descriptionIcon('heroicon-m-bolt')
                ->color('success'),
            Stat::make('昨日访客' . $mockSuffix, number_format($yesterdayUsers))
                ->description($isMock ? '请配置 Google Analytics 凭证' : '昨日页面浏览量 (PV): ' . number_format($yesterdayViews))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),
            Stat::make('本月访客' . $mockSuffix, number_format($thisMonthUsers))
                ->description($isMock ? '请配置 Google Analytics 凭证' : Carbon::now()->translatedFormat('n月') . '累计浏览量: ' . number_format($thisMonthViews))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
            Stat::make('上月访客' . $mockSuffix, number_format($previousMonthUsers))
                ->description($isMock ? '请配置 Google Analytics 凭证' : Carbon::now()->subMonth()->translatedFormat('n月') . '全月浏览量: ' . number_format($previousMonthViews))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
        ];
    }
}
