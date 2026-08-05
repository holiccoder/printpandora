<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\GoogleAnalyticsChart;

class Traffic extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.pages.traffic';

    protected static ?string $title = '流量分析';

    protected static ?string $navigationLabel = '流量分析';

    protected function getHeaderWidgets(): array
    {
        return [
            GoogleAnalyticsChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): array|int
    {
        return 1;
    }
}
