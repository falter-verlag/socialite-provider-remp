<?php

namespace Tests;

use Laravel\Socialite\Contracts\Factory;
use Remp\Auth\Socialite\Provider;

class ServiceProviderTest extends TestCase
{
    public function test_it_registers_the_remp_driver()
    {
        $socialite = $this->app->make(Factory::class);

        $driver = $socialite->driver('remp');

        $this->assertInstanceOf(Provider::class, $driver);
    }
}
