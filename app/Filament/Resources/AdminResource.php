<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminResource\Pages;
use App\Models\Admin;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;


class AdminResource extends Resource
{
    protected static ?string $model = Admin::class;
    //  protected static ?string $navigationLabel = 'Admins main'; // Ensure this is set
    // protected static ?string $navigationGroup = 'Settings'; // Optional, but ensure it matches
    // protected static ?string $slug = 'admins'; // Ensure this is set correctly

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('role', ['admin', 'sub-admin']);
    }


    public static function canCreate(): bool
    {
        return true; // ✅ Allow "New Admin"
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => !empty($state) ? Hash::make($state) : null)
                    ->required(fn($record) => $record === null),
                Select::make('role')
                    ->options([
                        // 'admin' => 'Admin',
                        'sub-admin' => 'Sub-Admin',
                    ])
                    ->required(),
                Forms\Components\CheckboxList::make('allowed_resources')
                    ->label('Allowed Resources')
                    ->options([
                        'products' => 'Products',
                        'orders' => 'Orders',
                        'coupons' => 'Coupons',

                    ])
                    ->columns(2)
                    ->helperText('Select the resources this sub-admin can access.'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table

            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('email')->sortable()->searchable(),
                TextColumn::make('role')->sortable(),
                TextColumn::make('created_at')->dateTime(),
            ])

            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'edit' => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }

    // public static function canCreate(): bool
    // {
    //     return auth()->check() && auth()->user()->hasPermissionTo('manage users');
    // }


}
