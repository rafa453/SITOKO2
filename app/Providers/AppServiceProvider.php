<?php

namespace App\Providers;

use App\Listeners\AutoClockInCashier;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.custom');

        Event::listen(Login::class, AutoClockInCashier::class);
    }
}
