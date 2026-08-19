<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\FourPxService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Throwable;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createFourPxShipment')
                ->label('Create 4PX shipment')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->visible(fn (Order $record): bool => $record->shipping_method === 'standard')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\TextInput::make('weight_grams')
                        ->label('Parcel weight (g)')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required()
                        ->default(fn (Order $record): mixed => $record->shipping_weight_grams ?: config('services.four_px.default_weight_grams')),
                    Forms\Components\TextInput::make('length_cm')
                        ->label('Length (cm)')
                        ->numeric()
                        ->minValue(0.01)
                        ->default(fn (Order $record): mixed => $record->shipping_length_cm ?: config('services.four_px.default_length_cm')),
                    Forms\Components\TextInput::make('width_cm')
                        ->label('Width (cm)')
                        ->numeric()
                        ->minValue(0.01)
                        ->default(fn (Order $record): mixed => $record->shipping_width_cm ?: config('services.four_px.default_width_cm')),
                    Forms\Components\TextInput::make('height_cm')
                        ->label('Height (cm)')
                        ->numeric()
                        ->minValue(0.01)
                        ->default(fn (Order $record): mixed => $record->shipping_height_cm ?: config('services.four_px.default_height_cm')),
                    Forms\Components\TextInput::make('logistics_product_code')
                        ->label('4PX logistics product code')
                        ->required()
                        ->default(config('services.four_px.logistics_product_code')),
                ])
                ->action(function (Order $record, array $data): void {
                    try {
                        app(FourPxService::class)->createShipment($record, $data);

                        Notification::make()
                            ->success()
                            ->title('4PX shipment created')
                            ->body('Refresh the order later if 4PX has not returned the final channel number yet.')
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('4PX shipment failed')
                            ->body(Str::limit($exception->getMessage(), 500, '...'))
                            ->send();
                    }
                }),
            Actions\Action::make('refreshFourPxShipment')
                ->label('Refresh 4PX status')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (Order $record): bool => $record->shipping_method === 'standard' && filled($record->fourpx_ref_no))
                ->action(function (Order $record): void {
                    try {
                        app(FourPxService::class)->refreshShipment($record);

                        Notification::make()
                            ->success()
                            ->title('4PX status refreshed')
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Unable to refresh 4PX status')
                            ->body(Str::limit($exception->getMessage(), 500, '...'))
                            ->send();
                    }
                }),
            Actions\Action::make('fetchFourPxLabel')
                ->label('Get 4PX label')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn (Order $record): bool => $record->shipping_method === 'standard' && filled($record->fourpx_ref_no))
                ->action(function (Order $record): void {
                    try {
                        app(FourPxService::class)->fetchLabel($record);

                        Notification::make()
                            ->success()
                            ->title('4PX label URL saved')
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Unable to get 4PX label')
                            ->body(Str::limit($exception->getMessage(), 500, '...'))
                            ->send();
                    }
                }),
            Actions\Action::make('refreshFourPxTracking')
                ->label('Refresh 4PX tracking')
                ->icon('heroicon-o-map-pin')
                ->visible(fn (Order $record): bool => $record->shipping_method === 'standard' && filled($record->tracking_number))
                ->action(function (Order $record): void {
                    try {
                        app(FourPxService::class)->refreshTracking($record);

                        Notification::make()
                            ->success()
                            ->title('4PX tracking refreshed')
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Unable to refresh 4PX tracking')
                            ->body(Str::limit($exception->getMessage(), 500, '...'))
                            ->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
