<?php

namespace Remp\Auth\Socialite;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Laravel\Socialite\Contracts\Factory;

class ServiceProvider extends LaravelServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Factory::class, function ($app) {
            return new Manager($app);
        });
    }
}
