<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return "Order #{$this->getRecord()->getKey()}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            OrderResource::designAction(),
        ];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record = $this->getRecord()->loadMissing([
            'items.product',
            'designServiceRequests',
        ]);
    }
}
