<?php

namespace Tests;

use Laravel\Socialite\SocialiteServiceProvider;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Remp\Auth\Socialite\ServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use MockeryPHPUnitIntegration;

    protected function getPackageProviders($app)
    {
        return [
            SocialiteServiceProvider::class,
            ServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('services.remp', [
            'client_id'     => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect'      => 'https://app.test/auth/remp/callback',
            'remp_url'      => 'https://crm.example.test',
        ]);
    }
}
