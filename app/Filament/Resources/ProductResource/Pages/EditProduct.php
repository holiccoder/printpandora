<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductImageService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('images')
                ->label('Manage images')
                ->icon('heroicon-o-photo')
                ->url(fn (): string => ProductResource::getUrl('images', ['record' => $this->getRecord()]))
                ->visible(function (): bool {
                    $record = $this->getRecord();

                    return $record instanceof Product && app(ProductImageService::class)->supportsBusinessCard($record);
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
