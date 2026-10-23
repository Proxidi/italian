<?php

namespace App\Providers;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

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
        // Do not enforce the map while the active legacy User still uses its
        // class name in existing Spatie assignments. Only Usuario gets an alias.
        Relation::morphMap(['usuario' => Usuario::class]);
    }
}
