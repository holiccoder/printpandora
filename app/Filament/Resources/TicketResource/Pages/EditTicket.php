<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\SupportTicketReply;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();

        if (! empty($data['reply_message'])) {
            SupportTicketReply::create([
                'ticket_id' => $this->record->id,
                'user_id' => null,
                'message' => $data['reply_message'],
            ]);
        }
    }
}
