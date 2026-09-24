<?php

namespace App\Filament\Resources\ShowcaseResource\Pages;

use App\Filament\Resources\ShowcaseResource;
use App\Models\Showcase;
use App\Models\ShowcaseCategory;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListShowcases extends ListRecords
{
    protected static string $resource = ShowcaseResource::class;

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('全部')
                ->badge(Showcase::query()->count()),
        ];

        foreach (ShowcaseCategory::query()
            ->withCount('showcases')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get() as $category) {
            $tabs[(string) $category->getKey()] = Tab::make($category->name)
                ->badge($category->showcases_count)
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('category_id', $category->getKey()),
                );
        }

        $tabs['uncategorized'] = Tab::make('未分类')
            ->badge(Showcase::query()->whereNull('category_id')->count())
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query->whereNull('category_id'),
            );

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('新建案例'),
        ];
    }
}
