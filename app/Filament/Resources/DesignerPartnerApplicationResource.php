<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DesignerPartnerApplicationResource\Pages;
use App\Models\DesignerPartnerApplication;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DesignerPartnerApplicationResource extends Resource
{
    protected static ?string $model = DesignerPartnerApplication::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 42;

    protected static ?string $modelLabel = 'Designer Partner Application';

    protected static ?string $pluralModelLabel = 'Designer Partner Applications';

    protected static ?string $navigationLabel = 'Designer Partner Applications';

    protected static string|\UnitEnum|null $navigationGroup = '商城管理';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Application details')
                    ->schema([
                        Forms\Components\TextInput::make('name_or_company')
                            ->label('Name or company')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('country_or_region')
                            ->label('Country or region')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('portfolio_links')
                            ->label('Portfolio links')
                            ->rows(4)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('design_field')
                            ->label('Primary design field')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('expected_products_finishes')
                            ->label('Expected products and finishes')
                            ->rows(4)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Review')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'new' => 'New',
                                'reviewing' => 'Reviewing',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('handled_at')
                            ->label('Handled at')
                            ->nullable(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Internal notes')
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Metadata')
                    ->schema([
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP address')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('user_agent')
                            ->label('User agent')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Submitted at')
                            ->disabled()
                            ->dehydrated(false),
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
                Tables\Columns\TextColumn::make('name_or_company')
                    ->label('Name or company')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('country_or_region')
                    ->label('Country or region')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('design_field')
                    ->label('Design field')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'reviewing' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('handled_at')
                    ->label('Handled at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'reviewing' => 'Reviewing',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\Filter::make('handled_at')
                    ->query(fn ($query) => $query->whereNull('handled_at'))
                    ->label('Unreviewed')
                    ->toggle(),
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDesignerPartnerApplications::route('/'),
            'edit' => Pages\EditDesignerPartnerApplication::route('/{record}/edit'),
        ];
    }
}
