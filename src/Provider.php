<?php

namespace Remp\Auth\Socialite;

use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class Provider extends AbstractProvider
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['default'];

    /**
     * Get the URL to the REMP instance, e.g. https://crm.press.
     *
     * @return string
     */
    public function getRempUrl()
    {
        return config('services.remp.remp_url', 'http://localhost:8080');
    }

    /**
     * @inheritdoc
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->getRempUrl() . '/authorize',
            $state,
        );
    }

    /**
     * Get the default options for an HTTP request.
     *
     * @param string $token
     * @return array
     */
    protected function getRequestOptions($token)
    {
        return [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getTokenUrl()
    {
        return $this->getRempUrl() . '/oauth/access_token';
    }

    /**
     * @inheritdoc
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            $this->getRempUrl() . '/api/v1/user/info?source=oauth_token',
            $this->getRequestOptions($token),
        );

        if ($response->getBody() instanceof Stream) {
            return json_decode($response->getBody()->getContents(), true);
        }

        return json_decode($response->getBody(), true);
    }

    /**
     * @inheritdoc
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'    => Arr::get($user, 'user.id'),
            'name'  => implode(
                ' ',
                array_filter([
                    Arr::get($user, 'user.first_name'),
                    Arr::get($user, 'user.last_name'),
                ]),
            ),
            'email' => Arr::get($user, 'user.email'),
        ]);
    }
}
