<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Filament\Resources\CouponResource\RelationManagers;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Coupon Code')
                    ->required()
                    ->unique(Coupon::class),

                Forms\Components\TextInput::make('discount')
                    ->label('Discount Value')
                    ->numeric()
                    ->required(),

                Forms\Components\Select::make('discount_type')
                    ->label('Discount Type')
                    ->options([
                        'fixed' => 'Fixed Amount',
                        'percentage' => 'Percentage',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('usage_limit')
                    ->label('Usage Limit')
                    ->numeric()
                    ->nullable(),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expiry Date')
                    ->nullable(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            Tables\Columns\TextColumn::make('code')
                ->label('Code')
                ->searchable(),

            Tables\Columns\TextColumn::make('discount')
                ->label('Discount')
                ->sortable(),

            Tables\Columns\TextColumn::make('discount_type')
                ->label('Type'),

            Tables\Columns\TextColumn::make('usage_limit')
                ->label('Usage Limit')
                ->sortable(),

            Tables\Columns\TextColumn::make('used_count')
                ->label('Used Count')
                ->sortable(),

            Tables\Columns\TextColumn::make('expires_at')
                ->label('Expires At')
                ->dateTime()
                ->sortable(),

            Tables\Columns\ToggleColumn::make('is_active')
                ->label('Active'),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('discount_type')
                ->options([
                    'fixed' => 'Fixed Amount',
                    'percentage' => 'Percentage',
                ]),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
