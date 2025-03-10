<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */

    public function boot(): void
    {


        //

            Filament::serving(function () {
                $user = auth()->user();

                if ($user && $user->role === 'sub-admin') {
                    // Filter navigation based on allowed resources
                    // Filament::registerNavigationItems(
                    //     collect(Filament::getNavigation())->filter(function ($item) use ($user) {
                    //         return in_array($item->getId(), $user->allowed_resources ?? []);
                    //     })->toArray()
                    // );
                }
            });

    }
}
