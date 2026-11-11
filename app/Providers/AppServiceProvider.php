<?php

namespace App\Providers;

use App\Models\Liga;
use App\Services\TournamentService;
use Illuminate\Support\Facades\View;
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
        View::composer('partials.running_text', function ($view) {
            $liga = Liga::current();
            $ticker = TournamentService::getTickerMatches($liga);
            $view->with($ticker);
        });
    }
}
