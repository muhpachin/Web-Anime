<?php

namespace App\Providers;

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
    view()->composer('*', function ($view) {
        $view->with('holidaySettings', [
            'christmas' => \App\Models\SiteSetting::where('key', 'christmas_mode')->first()?->value == '1',
            'new_year' => \App\Models\SiteSetting::where('key', 'new_year_mode')->first()?->value == '1',
        ]);
    });
}
}
