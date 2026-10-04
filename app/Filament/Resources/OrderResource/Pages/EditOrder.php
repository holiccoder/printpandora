<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\FourPxService;
use App\Services\OrderWorkflowService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Throwable;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        if (! $record instanceof Order) {
            return $data;
        }

        $requestedStatus = (string) ($data['status'] ?? $record->status);

        if (
            $requestedStatus !== $record->status
            && ! app(OrderWorkflowService::class)->canTransition($record, $requestedStatus)
        ) {
            Notification::make()
                ->danger()
                ->title('订单状态变更不符合流程')
                ->body('请按照付款、文件审核、客户确认和生产的顺序推进订单。')
                ->send();

            $data['status'] = $record->status;
        }

        if (
            ($data['status'] ?? null) === Order::STATUS_CONFIRMED
            && $record->status !== Order::STATUS_CONFIRMED
        ) {
            Notification::make()
                ->danger()
                ->title('需要客户确认')
                ->body('请先通过文件流程提交文件并等待客户确认。')
                ->send();

            $data['status'] = $record->status;
        }

        if (
            ($data['status'] ?? null) === Order::STATUS_PRODUCTION
            && ! in_array($record->status, [
                Order::STATUS_CONFIRMED,
                Order::STATUS_PRODUCTION,
                Order::STATUS_SHIPPED,
            ], true)
        ) {
            Notification::make()
                ->danger()
                ->title('生产前需要客户确认')
                ->body('客户确认文件后，订单才能进入生产。')
                ->send();

            $data['status'] = $record->status;
        }

        // The edit form persists the status directly after this hook. Keep
        // the reminder timestamps in sync with the same workflow rules used
        // by the table status column and file actions.
        $finalStatus = (string) ($data['status'] ?? $record->status);

        if ($finalStatus !== $record->status) {
            if ($finalStatus === Order::STATUS_PENDING_CONFIRMATION) {
                $data['confirmation_requested_at'] = now();
                $data['confirmation_reminded_at'] = null;
            } elseif (in_array($finalStatus, [
                Order::STATUS_PENDING_REVIEW,
                Order::STATUS_NEEDS_REUPLOAD,
            ], true)) {
                $data['confirmation_requested_at'] = null;
                $data['confirmation_reminded_at'] = null;
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            OrderResource::fileAction(),
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
            Actions\DeleteAction::make()->label('删除订单'),
        ];
    }
}
