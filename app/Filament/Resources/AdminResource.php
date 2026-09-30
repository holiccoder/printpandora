<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminResource\Pages;
use App\Models\Admin;
use Filament\Actions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminResource extends Resource
{
    protected static ?string $model = Admin::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $modelLabel = '管理员';

    protected static ?string $pluralModelLabel = '管理员';

    protected static string|\UnitEnum|null $navigationGroup = '用户管理';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('管理员信息')
                    ->schema([
                        TextInput::make('name')
                            ->label('姓名')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('电子邮箱')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        DateTimePicker::make('email_verified_at')
                            ->label('邮箱验证时间'),
                        TextInput::make('password')
                            ->label('密码')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('角色与权限')
                    ->description('角色决定管理员可以访问的后台资源和操作。')
                    ->schema([
                        Select::make('roles')
                            ->label('角色')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('guard_name', 'admin')
                                    ->when(
                                        ! auth('admin')->user()?->hasRole('super_admin'),
                                        fn (Builder $query): Builder => $query->where('name', '!=', 'super_admin'),
                                    ),
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn (): array => [
                                (int) Role::query()
                                    ->where('name', 'panel_user')
                                    ->where('guard_name', 'admin')
                                    ->value('id'),
                            ]),
                        CheckboxList::make('permissions')
                            ->label('直接权限')
                            ->relationship(
                                name: 'permissions',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->where('guard_name', 'admin'),
                            )
                            ->columns(2)
                            ->bulkToggleable()
                            ->visible(fn (): bool => (bool) auth('admin')->user()?->hasRole('super_admin')),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('电子邮箱')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('角色')
                    ->badge()
                    ->separator('、'),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('直接权限数')
                    ->counts('permissions')
                    ->sortable(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('已验证邮箱')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Actions\EditAction::make(),
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
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'edit' => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }
}
