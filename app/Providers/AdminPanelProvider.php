<?php

namespace App\Providers;

use App\Filament\Resources\CouponResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\UserResource;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class AdminPanelProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Log::info('boot test time:');
        Filament::serving(function () {
            Gate::define('accessFilament', function ($user) {
                return $this->hasAccess($user);
            });

            // ✅ Call getNavigationItems() directly instead of passing a Closure
            Filament::registerNavigationItems($this->getNavigationItems());
        });
    }

    private function hasAccess($user): bool
    {
        if (!$user) {
            return false;
        }

        // Fetch the role from the database directly
        $role = DB::table('users')->where('id', $user->id)->value('role');

        return $role === 'admin' || in_array('products', $user->allowed_resources ?? []);
    }
    // private function getNavigationItems(): array
    // {
    //     $user = auth()->user();

    //     if (!$user) {
    //         return [];
    //     }

    //     $role = DB::table('users')->where('id', $user->id)->value('role');
    //     $allowedResources = $user->allowed_resources ?? [];

    //     // Get the default navigation items from each resource
    //     $resourceNavigationItems = [
    //         'products' => ProductResource::getNavigationItems()[0] ?? null,
    //         // 'orders' => OrderResource::getNavigationItems()[0] ?? null,
    //         'coupons' => CouponResource::getNavigationItems()[0] ?? null,
    //         'users' => UserResource::getNavigationItems()[0] ?? null,
    //     ];

    //     // Remove null values (if a resource has no navigation items)
    //     $resourceNavigationItems = array_filter($resourceNavigationItems);

    //     // If the user is an admin, show everything
    //     if ($role === 'admin') {
    //         return array_values($resourceNavigationItems);
    //     }

    //     // Debugging logs
    //     Log::info('All Navigation Items Before Filtering:', $resourceNavigationItems);
    //     Log::info('Allowed Resources:', ['allowedResources' => $allowedResources]);

    //     // **🛠 FIXED: Proper Filtering**
    //     $filteredNavigation = [];
    //     foreach ($resourceNavigationItems as $key => $navItem) {
    //         if (in_array($key, $allowedResources)) {
    //             $filteredNavigation[] = $navItem;
    //         }
    //     }

    //     // Log the final filtered navigation
    //     Log::info('Filtered Navigation Items here:', $filteredNavigation);

    //     return $filteredNavigation;
    // }

    // private function getNavigationItems(): array
    // {
    //     $user = auth()->user();

    //     if (!$user) {
    //         return [];
    //     }
    //     $role = DB::table('users')->where('id', $user->id)->value('role');
    //     $allowedResources = $user->allowed_resources ?? [];

    //     // Get the default navigation items from each resource
    //     $resourceNavigationItems = [
    //         'products' => ProductResource::getNavigationItems()[0] ?? null,
    //         // 'orders' => OrderResourc::getNavigationItems()[0] ?? null,
    //         'coupons' => CouponResource::getNavigationItems()[0] ?? null,
    //         'users' => UserResource::getNavigationItems()[0] ?? null,
    //     ];

    //     // Remove null values (if a resource has no navigation items)
    //     $resourceNavigationItems = array_filter($resourceNavigationItems);

    //     // If the user is an admin, show everything
    //     // if ($user->hasRole('Admin')) {
    //     //     return array_values($resourceNavigationItems);
    //     // }
    //     if ($role === 'admin') {
    //         return array_values($resourceNavigationItems);
    //     }
    //     Log::info('resourceNavigationItems:', ['resourceNavigationItems' => $resourceNavigationItems]);
    //     Log::info('allowedResources test hh:', ['allowedResources' => $allowedResources]);
    //     // Filter only allowed resources
    //     $filteredNavigation = array_values(array_filter($resourceNavigationItems, function ($key) use ($allowedResources) {
    //         return in_array($key, $allowedResources);
    //     }, ARRAY_FILTER_USE_KEY));

    //     // Log the final filtered navigation
    //     Log::info('Filtered Navigation Items:', $filteredNavigation);

    //     return $filteredNavigation;
    // }
    private function getNavigationItems(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        // Fetch user role from the database
        $role = DB::table('users')->where('id', $user->id)->value('role');
        $allowedResources = $user->allowed_resources ?? [];
        Log::info('User Role test time mmm:', ['allowed_resources' => $user->allowed_resources]);

        // Define all available menu items
        $allNavigationItems = [
            // 'dashboard' => NavigationItem::make()
            //     ->label('Dashboard')
            //     ->url(route('filament.resources.dashboard'))
            //     ->icon('heroicon-o-home'),

            'products' => NavigationItem::make()
                ->label('Products')
                ->url(route('filament.resources.products.index')) // ✅ Correct Filament v2 route
                ->icon('heroicon-o-shopping-cart'),
            'orders' => NavigationItem::make()
                ->label('Orders')
                ->url(route('filament.resources.orders.index'))
                ->icon('heroicon-o-truck'), // 🚚 More suitable icon for orders/shipping

            'coupons' => NavigationItem::make()
                ->label('Coupons')
                ->url(route('filament.resources.coupons.index')) // ✅ Correct Filament v2 route
                ->icon('heroicon-o-ticket'),

            'users' => NavigationItem::make()
                ->label('Users')
                ->url(route('filament.resources.users.index')) // ✅ Correct Filament v2 route
                ->icon('heroicon-o-user-group'),
            'admins' => NavigationItem::make()
                ->label('Admins')
                ->url(route('filament.resources.admins.index')) // ✅ Correct Filament v2 route
                ->icon('heroicon-o-user-circle'),



        ];

        Log::info('allNavigationItems test:', ['allNavigationItems' =>   $allNavigationItems]);

        // If the user is an admin, show all items
        if ($role === 'admin') {
            return array_values($allNavigationItems);
        }

        // If the user is a sub-admin, filter menu items
        $filteredNavigationItems = array_filter($allNavigationItems, function ($key) use ($allowedResources) {
            return in_array($key, $allowedResources);
        }, ARRAY_FILTER_USE_KEY);

        // Log the filtered navigation items
        Log::info('Filtered Navigation Items:', [
            'filtered_items' => $filteredNavigationItems,
        ]);

        return array_values($filteredNavigationItems);
    }
}
