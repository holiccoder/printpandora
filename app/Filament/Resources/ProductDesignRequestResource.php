<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductDesignRequestResource\Pages;
use App\Models\ProductDesignRequest;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductDesignRequestResource extends Resource
{
    protected static ?string $model = ProductDesignRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 41;

    protected static ?string $modelLabel = '产品设计提交';

    protected static ?string $pluralModelLabel = '产品设计提交';

    protected static ?string $navigationLabel = '产品设计提交';

    protected static string|\UnitEnum|null $navigationGroup = '商城管理';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('产品设计提交')
                    ->schema([
                        TextEntry::make('desgin.mode')
                            ->label('提交方式'),
                        TextEntry::make('desgin.product_name')
                            ->label('产品'),
                        TextEntry::make('desgin.email')
                            ->label('邮箱'),
                        TextEntry::make('created_at')
                            ->label('提交时间')
                            ->dateTime(),
                        TextEntry::make('desgin')
                            ->label('JSON 内容')
                            ->formatStateUsing(static function (mixed $state): string {
                                if (! is_array($state)) {
                                    return (string) $state;
                                }

                                return (string) json_encode(
                                    $state,
                                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                                );
                            })
                            ->columnSpanFull()
                            ->copyable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('mode')
                    ->label('提交方式')
                    ->state(fn (ProductDesignRequest $record): string => (string) data_get($record->desgin, 'mode', '—'))
                    ->badge(),
                Tables\Columns\TextColumn::make('product')
                    ->label('产品')
                    ->state(fn (ProductDesignRequest $record): string => (string) data_get($record->desgin, 'product_name', '—')),
                Tables\Columns\TextColumn::make('email')
                    ->label('邮箱')
                    ->state(fn (ProductDesignRequest $record): string => (string) data_get($record->desgin, 'email', '—')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('提交时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductDesignRequests::route('/'),
            'view' => Pages\ViewProductDesignRequest::route('/{record}'),
        ];
    }
}
