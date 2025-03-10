<?php

namespace App\Providers;

use App\Filament\Resources\AdminResource;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Filament::registerResources([
        //     AdminResource::class,
        //     // Other resources...
        // ]);



        Filament::serving(function () {
            $user = auth()->user();

            if ($user && $user->role === 'sub-admin') {
                // Filament::registerNavigationGroups([
                //     NavigationGroup::make('User Management'), // ✅ REMOVE getId()
                // ]);
                // Filament::registerNavigationItems(
                //     collect(Filament::getNavigation())->filter(function ($item) use ($user) {
                //         return in_array($item->getId(), $user->allowed_resources ?? []);
                //     })->toArray()
                // );
            }
        });
    }
}
