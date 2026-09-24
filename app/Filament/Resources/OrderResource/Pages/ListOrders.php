<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('全部')
                ->badge(Order::query()->count()),
        ];

        foreach (Order::statusOptions() as $status => $label) {
            $tabs[$status] = Tab::make($label)
                ->badge(Order::query()->where('status', $status)->count())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', $status),
                );
        }

        return $tabs;
    }
}
