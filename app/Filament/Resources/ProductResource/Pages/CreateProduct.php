<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductConfigurationService;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** @var array<string, mixed> */
    protected array $configurationFormData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->configurationFormData = $data;
        unset($data['product_config']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if ($record instanceof Product) {
            app(ProductConfigurationService::class)->saveResource(
                $record,
                $this->configurationFormData,
            );
        }
    }
}
