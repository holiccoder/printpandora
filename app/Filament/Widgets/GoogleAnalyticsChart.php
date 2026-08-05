<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;
use Illuminate\Support\Carbon;

class GoogleAnalyticsChart extends ChartWidget
{
    protected ?string $heading = '网站访问流量统计 (GA4)';

    protected ?string $description = '过去 30 天的独立访客与页面浏览量';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        try {
            // Attempt to query GA4 data for the last 30 days
            $analyticsData = Analytics::fetchVisitorsAndPageViews(Period::days(30));
            
            $labels = [];
            $activeUsers = [];
            $pageViews = [];

            foreach ($analyticsData as $row) {
                $labels[] = Carbon::parse($row['date'])->translatedFormat('n月j日');
                $activeUsers[] = (int) $row['activeUsers'];
                $pageViews[] = (int) $row['screenPageViews'];
            }

            $this->description = '过去 30 天的独立访客与页面浏览量趋势（来自 Google Analytics）';
        } catch (\Exception $e) {
            // If GA4 is not configured yet, fallback to mock data gracefully with a warning description
            $this->description = '⚠️ Google Analytics 凭证未配置。当前显示演示数据。请在 .env 中设置 ANALYTICS_PROPERTY_ID 并配置 service-account-credentials.json';
            
            $days = collect(range(29, 0))->map(fn (int $i) => Carbon::now()->subDays($i));
            $labels = $days->map(fn (Carbon $date) => $date->translatedFormat('n月j日'))->toArray();
            
            // Generate some plausible mock data for demonstration
            $activeUsers = [
                120, 150, 180, 140, 160, 210, 250, 220, 230, 190,
                240, 260, 290, 270, 280, 310, 340, 320, 300, 310,
                350, 380, 410, 390, 370, 420, 450, 430, 460, 490
            ];
            $pageViews = [
                350, 420, 510, 390, 450, 620, 710, 680, 650, 590,
                690, 750, 820, 790, 810, 920, 990, 940, 890, 910,
                1050, 1120, 1250, 1190, 1110, 1280, 1390, 1310, 1420, 1550
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => '独立访客 (Active Users)',
                    'data' => $activeUsers,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
                [
                    'label' => '页面浏览量 (Page Views)',
                    'data' => $pageViews,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
