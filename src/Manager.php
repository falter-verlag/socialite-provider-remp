<?php

namespace Remp\Auth\Socialite;

use Laravel\Socialite\SocialiteManager;

class Manager extends SocialiteManager
{
    protected function createRempDriver()
    {
        $config = $this->config->get('services.remp');

        return $this->buildProvider(
            Provider::class,
            $config
        );
    }
}
