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
                ->label('创建 4PX 货运单')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->visible(fn (Order $record): bool => $record->shipping_method === 'standard')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\TextInput::make('weight_grams')
                        ->label('包裹重量（克）')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required()
                        ->default(fn (Order $record): mixed => $record->shipping_weight_grams ?: config('services.four_px.default_weight_grams')),
                    Forms\Components\TextInput::make('logistics_product_code')
                        ->label('4PX 物流产品代码')
                        ->required()
                        ->default(config('services.four_px.logistics_product_code')),
                ])
                ->action(function (Order $record, array $data): void {
                    try {
                        app(FourPxService::class)->createShipment($record, $data);

                        Notification::make()
                            ->success()
                            ->title('4PX 货运单已创建')
                            ->body('如果 4PX 尚未返回最终渠道号，请稍后刷新订单。')
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('4PX 货运单创建失败')
                            ->body(Str::limit($exception->getMessage(), 500, '...'))
                            ->send();
                    }
                }),
            Actions\Action::make('refreshFourPxShipment')
                ->label('刷新 4PX 状态')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (Order $record): bool => $record->shipping_method === 'standard' && filled($record->fourpx_ref_no))
                ->action(function (Order $record): void {
                    try {
                        app(FourPxService::class)->refreshShipment($record);

                        Notification::make()
                            ->success()
                            ->title('4PX 状态已刷新')
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('无法刷新 4PX 状态')
                            ->body(Str::limit($exception->getMessage(), 500, '...'))
                            ->send();
                    }
                }),
            Actions\Action::make('fetchFourPxLabel')
                ->label('获取 4PX 面单')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn (Order $record): bool => $record->shipping_method === 'standard' && filled($record->fourpx_ref_no))
                ->action(function (Order $record): void {
                    try {
                        app(FourPxService::class)->fetchLabel($record);

                        Notification::make()
                            ->success()
                            ->title('4PX 面单 URL 已保存')
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('无法获取 4PX 面单')
                            ->body(Str::limit($exception->getMessage(), 500, '...'))
                            ->send();
                    }
                }),
            Actions\Action::make('refreshFourPxTracking')
                ->label('刷新 4PX 物流追踪')
                ->icon('heroicon-o-map-pin')
                ->visible(fn (Order $record): bool => $record->shipping_method === 'standard' && filled($record->tracking_number))
                ->action(function (Order $record): void {
                    try {
                        app(FourPxService::class)->refreshTracking($record);

                        Notification::make()
                            ->success()
                            ->title('4PX 物流追踪已刷新')
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('无法刷新 4PX 物流追踪')
                            ->body(Str::limit($exception->getMessage(), 500, '...'))
                            ->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
