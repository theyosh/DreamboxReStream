<?php

namespace App\Providers;

use App\Dreambox;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Dreambox::saving(function ($dreambox) {
            // Strip the (last) char from the string, this will case problems
            // TODO: Use a regex
            $dreambox->exclude_bouquets = trim($dreambox->exclude_bouquets,',');
        });
    }
}
