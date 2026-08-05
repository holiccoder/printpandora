<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserRegistrationsChart extends ChartWidget
{
    protected ?string $heading = '用户注册';

    protected ?string $description = '新增注册用户趋势';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => '最近 7 天',
            '30' => '最近 30 天',
            '90' => '最近 90 天',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $range = (int) ($this->filter ?? 30);
        $days = collect(range($range - 1, 0))->map(fn (int $i) => Carbon::now()->subDays($i));

        $labels = $days->map(fn (Carbon $date) => $date->translatedFormat('n月j日'));

        $usersByDay = User::select(
            DB::raw('DATE(created_at) as day'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', Carbon::now()->subDays($range - 1)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $userCounts = $days->map(fn (Carbon $date) => (int) ($usersByDay->get($date->format('Y-m-d'))?->count ?? 0));

        return [
            'datasets' => [
                [
                    'label' => '新用户',
                    'data' => $userCounts->values()->toArray(),
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels->values()->toArray(),
        ];
    }
}
