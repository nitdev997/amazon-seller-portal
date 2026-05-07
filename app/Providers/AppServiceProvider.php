<?php

namespace App\Providers;
use Livewire\Livewire;
use App\Livewire\Orders\OrderTable;

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
        Livewire::component('orders.order-table', OrderTable::class);
    }
}
