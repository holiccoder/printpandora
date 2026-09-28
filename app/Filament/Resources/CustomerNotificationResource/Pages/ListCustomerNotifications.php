<?php

namespace App\Filament\Resources\CustomerNotificationResource\Pages;

use App\Filament\Resources\CustomerNotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerNotifications extends ListRecords
{
    protected static string $resource = CustomerNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('批量发送通知'),
        ];
    }
}
