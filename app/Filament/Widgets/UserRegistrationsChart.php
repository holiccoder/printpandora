<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserRegistrationsChart extends ChartWidget
{
    protected ?string $heading = 'User Registrations';

    protected ?string $description = 'Last 7 days';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(fn (int $i) => Carbon::now()->subDays($i));

        $labels = $days->map(fn (Carbon $date) => $date->format('M d'));

        $usersByDay = User::select(
            DB::raw('DATE(created_at) as day'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $userCounts = $days->map(fn (Carbon $date) => (int) ($usersByDay->get($date->format('Y-m-d'))?->count ?? 0));

        return [
            'datasets' => [
                [
                    'label' => 'New users',
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
