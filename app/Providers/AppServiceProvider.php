<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

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
        // WAJIB: Memerintahkan Laravel untuk memuat rute otentikasi WebSocket (channels.php)
        Broadcast::routes();

        // Memastikan file channels.php dibaca saat aplikasi pertama kali dimuat
        if (file_exists(base_path('routes/channels.php'))) {
            require base_path('routes/channels.php');
        }
    }
}