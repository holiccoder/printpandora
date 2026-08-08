<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductConfigurationService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    /** @var array<string, mixed> */
    protected array $configurationFormData = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if ($record instanceof Product) {
            return array_replace($data, app(ProductConfigurationService::class)->resourceFormState($record));
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->configurationFormData = $data;
        unset($data['product_config']);

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if ($record instanceof Product) {
            app(ProductConfigurationService::class)->saveResource($record, $this->configurationFormData);
        }
    }
}
