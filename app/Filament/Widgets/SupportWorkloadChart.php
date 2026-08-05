<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SupportWorkloadChart extends ChartWidget
{
    protected ?string $heading = '待处理工单负载 (按优先级)';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $ticketCounts = DB::table('support_tickets')
            ->where('status', '!=', 'closed')
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $priorities = [
            'high' => '高优先级 (High)',
            'medium' => '中优先级 (Medium)',
            'low' => '低优先级 (Low)',
        ];

        $colors = [
            'high' => '#ef4444',
            'medium' => '#f59e0b',
            'low' => '#10b981',
        ];

        $labels = [];
        $data = [];
        $backgroundColor = [];

        foreach ($priorities as $key => $label) {
            $labels[] = $label;
            $data[] = (int) ($ticketCounts[$key] ?? 0);
            $backgroundColor[] = $colors[$key] ?? '#6b7280';
        }

        return [
            'datasets' => [
                [
                    'label' => '待处理工单数',
                    'data' => $data,
                    'backgroundColor' => $backgroundColor,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
