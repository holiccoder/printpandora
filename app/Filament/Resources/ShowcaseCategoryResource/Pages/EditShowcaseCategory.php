<?php

namespace App\Filament\Resources\ShowcaseCategoryResource\Pages;

use App\Filament\Resources\ShowcaseCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShowcaseCategory extends EditRecord
{
    protected static string $resource = ShowcaseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
