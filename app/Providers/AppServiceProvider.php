<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

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
        // Don't kill the app if the database hasn't been created.
        // https://www.whoishostingthis.com/compare/sqlite/optimize/
        // https://medium.com/swlh/laravel-optimizing-sqlite-to-dangerous-speeds-ff04111b1f22
        try {
            DB::connection('sqlite')->statement(
                'PRAGMA synchronous = OFF;'
            );
            DB::connection('sqlite')->statement(
                'PRAGMA journal_mode = MEMORY;'
            );
            DB::connection('sqlite')->statement(
                'PRAGMA temp_store = MEMORY;'
            );
        } catch (\Throwable $throwable) {
            return;
        }
    }
}
