<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use App\Models\User;
use Filament\Resources\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';
    // Disable default navigation item
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Product Name')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Product Description')
                    ->maxLength(1000),

                TextInput::make('price')
                    ->numeric()
                    ->label('Price')
                    ->required(),

                TextInput::make('stock')
                    ->numeric()
                    ->label('Stock Quantity')
                    ->required(),

                FileUpload::make('images')
                    ->image()
                    ->multiple()
                    ->maxFiles(5)
                    ->directory('products')
                    ->storeFileNamesIn('images') // Ensures it's saved as an array
                    ->required(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),

                ImageColumn::make('images')
                    ->label('Image')
                    ->getStateUsing(function ($record) {
                        // Decode JSON if stored as a string
                        $images = is_string($record->images) ? json_decode($record->images, true) : $record->images;

                        // Log the images data for debugging
                        if (is_array($images) && !empty($images)) {
                            $firstImage = array_values($images)[0]; // Get the first image path
                            Log::info('Product Images:', ['first_image' => $firstImage]); // Pass context as an array
                            // return $firstImage; // Return the first image path

                            $appUrl = config('app.url'); // Get APP_URL from .env
                            Log::info(' Images url:', ['appUrl' =>  rtrim($appUrl, '/') . '/storage/' . ltrim($firstImage, '/')]); // Pass context as an array
                            return rtrim($appUrl, '/') . '/storage/' . ltrim($firstImage, '/');
                        }
                        // Return null if no images are found
                        return null;
                    })
                    ->size(50),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Price')
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable(),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn($state) => $state ? 'Active' : 'Inactive')

            ])
            ->filters([
                Filter::make('Active Products')
                    ->query(fn($query) => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
    // public static function canViewAny(): bool
    // {
    //     $user = Auth::user();

    //     if (!$user) {
    //         return false; // No authenticated user
    //     }

    //     // Fetch the role directly from the database
    //     $role = $user->getRoleFromDatabase();

    //     return $role === 'admin' || in_array('products', $user->allowed_resources ?? []);
    // }
    public static function canViewAny(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false; // No authenticated user
        }

        // Fetch the role directly from the database
        $role = DB::table('users')
            ->where('id', $user->id)
            ->value('role'); // Assuming 'role' is a column in the 'users' table
        // Log the user's role and allowed_resources for debugging
        Log::info('User Role:', ['role' => $role]);
        Log::info('Allowed Resources:', ['allowed_resources' => $user->allowed_resources]);

        return $role === 'admin' || in_array('products', $user->allowed_resources ?? []);
    }
}
