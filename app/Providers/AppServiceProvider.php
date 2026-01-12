<?php

namespace App\Providers;

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
        // Daftarkan subfolder migrations agar terbaca otomatis
        $mainPath = database_path('migrations');
        $paths = array_merge([$mainPath], glob($mainPath . '/*', GLOB_ONLYDIR));

        $this->loadMigrationsFrom($paths);
    }
}
