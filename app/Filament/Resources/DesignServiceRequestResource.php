<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DesignServiceRequestResource\Pages;
use App\Models\DesignServiceRequest;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DesignServiceRequestResource extends Resource
{
    protected static ?string $model = DesignServiceRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static string|\UnitEnum|null $navigationGroup = 'Shop';

    protected static ?string $label = 'Design Service Requests';

    protected static ?string $navigationLabel = 'Design Requests';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Request Details')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('business_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('business_card_type')
                            ->label('Business Card Type')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('terms_accepted')
                            ->label('Terms Accepted')
                            ->required(),
                        Forms\Components\DateTimePicker::make('handled_at')
                            ->label('Handled At')
                            ->nullable(),
                    ]),
                Section::make('Card Information')
                    ->schema([
                        Forms\Components\Textarea::make('card_info')
                            ->label('Card Info')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
                Section::make('Admin Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Metadata')
                    ->schema([
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP Address')
                            ->disabled(),
                        Forms\Components\TextInput::make('user_agent')
                            ->label('User Agent')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Submitted At')
                            ->disabled(),
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
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('business_card_type')
                    ->label('Card Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('terms_accepted')
                    ->label('Terms')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Submitted'),
                Tables\Columns\TextColumn::make('handled_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Handled')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('terms_accepted')
                    ->label('Terms Accepted'),
                Tables\Filters\Filter::make('handled_at')
                    ->query(fn ($query) => $query->whereNull('handled_at'))
                    ->label('Pending')
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
            'index' => Pages\ListDesignServiceRequests::route('/'),
            'edit' => Pages\EditDesignServiceRequest::route('/{record}/edit'),
        ];
    }
}
