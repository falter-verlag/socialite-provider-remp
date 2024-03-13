<?php

namespace Remp\Auth\Socialite;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\SocialiteManager;

class ServiceProvider extends LaravelServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function boot()
    {
        /** @var SocialiteManager $socialite */
        $socialite = $this->app->make(Factory::class);

        $socialite->extend('remp', function () use ($socialite) {
            $config = config('services.remp');

            return $socialite->buildProvider(Provider::class, $config);
        });
    }
}
