<?php

namespace App\Providers;

use App\Composers;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));
        }

        View::composer('components.categories', Composers\Categories::class);
        View::composer('components.brands', Composers\Brands::class);
        View::composer('components.features', Composers\Features::class);
        View::composer('components.tags', Composers\Tags::class);
        View::composer('components.colors', Composers\Colors::class);
        View::composer('components.years', Composers\Years::class);

        // Custom 'if' template variables for various roles
        Blade::if('editor', function () {
            return auth()->check() && auth()->user()->editor();
        });
        Blade::if('moderator', function () {
            return auth()->check() && auth()->user()->moderator();
        });
        Blade::if('manager', function () {
            return auth()->check() && auth()->user()->manager();
        });
        Blade::if('admin', function () {
            return auth()->check() && auth()->user()->admin();
        });
        Blade::if('dev', function () {
            return auth()->check() && auth()->user()->developer();
        });
        Blade::if('junior', function () {
            return auth()->check() && auth()->user()->editor();
        });
        Blade::if('lolibrarian', function () {
            return auth()->check() && auth()->user()->moderator();
        });
        Blade::if('senior', function () {
            return auth()->check() && auth()->user()->manager();
        });

    }
}
