<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Product;
use App\Models\User;
use App\Models\Order;

class DashboardStats extends BaseWidget
{
    protected static ?int $sort = 1; // Ensures this widget loads first
    protected static bool $isLazy = false; // Ensures it loads immediately
    protected function getCards(): array
    {
        return [
            Card::make('Total Products', Product::count()),
            Card::make('Total Users', User::where('role', 'user')->count()),
            Card::make('Total Orders', Order::count()),
        ];
    }
}
