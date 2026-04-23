<?php

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use Remp\Auth\Socialite\Provider;

class ProviderTest extends TestCase
{
    private function makeProvider(?Request $request = null): Provider
    {
        return new Provider(
            $request ?? Request::create('/'),
            'test-client-id',
            'test-client-secret',
            'https://app.test/auth/remp/callback'
        );
    }

    private function requestWithSession(array $query = []): Request
    {
        $request = Request::create('/', 'GET', $query);
        $request->setLaravelSession($this->app['session.store']);

        return $request;
    }

    private function jsonResponse(array $payload): ResponseInterface
    {
        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturn(Utils::streamFor(json_encode($payload)));

        return $response;
    }

    private function rempUserPayload(array $overrides = []): array
    {
        return ['user' => array_merge([
            'id' => 42,
            'first_name' => 'Toni',
            'last_name' => 'Hofer',
            'email' => 'toni@example.test',
        ], $overrides)];
    }

    private function providerReturningUser(array $payload): Provider
    {
        $http = m::mock(Client::class);
        $http->shouldReceive('get')
            ->with('https://crm.example.test/api/v1/user/info?source=oauth_token', m::any())
            ->andReturn($this->jsonResponse($payload));

        return $this->makeProvider()->setHttpClient($http);
    }

    public function test_maps_user_to_object_with_full_name()
    {
        $user = $this->providerReturningUser($this->rempUserPayload())->userFromToken('token');

        $this->assertInstanceOf(SocialiteUser::class, $user);
        $this->assertSame(42, $user->getId());
        $this->assertSame('Toni Hofer', $user->getName());
        $this->assertSame('toni@example.test', $user->getEmail());
    }

    public function test_maps_user_with_only_first_name()
    {
        $user = $this->providerReturningUser(
            $this->rempUserPayload(['last_name' => null])
        )->userFromToken('token');

        $this->assertSame('Toni', $user->getName());
    }

    public function test_maps_user_with_only_last_name()
    {
        $user = $this->providerReturningUser(
            $this->rempUserPayload(['first_name' => null])
        )->userFromToken('token');

        $this->assertSame('Hofer', $user->getName());
    }

    public function test_returns_configured_remp_url()
    {
        $this->assertSame('https://crm.example.test', $this->makeProvider()->getRempUrl());
    }

    public function test_returns_default_remp_url_when_not_configured()
    {
        config()->set('services.remp', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect' => 'https://app.test/auth/remp/callback',
        ]);

        $this->assertSame('http://localhost:8080', $this->makeProvider()->getRempUrl());
    }

    public function test_redirect_builds_authorization_url()
    {
        $provider = $this->makeProvider($this->requestWithSession());

        $response = $provider->redirect();

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $url = $response->getTargetUrl();
        $this->assertStringStartsWith('https://crm.example.test/oauth/authorize?', $url);
        $this->assertStringContainsString('client_id=test-client-id', $url);
        $this->assertStringContainsString('redirect_uri='.urlencode('https://app.test/auth/remp/callback'), $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('scope=default', $url);
        $this->assertMatchesRegularExpression('/[?&]state=[^&]+/', $url);
    }

    public function test_get_user_by_token_sends_bearer_header()
    {
        $payload = $this->rempUserPayload();

        $http = m::mock(Client::class);
        $http->shouldReceive('get')
            ->once()
            ->with(
                'https://crm.example.test/api/v1/user/info?source=oauth_token',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer TKN',
                    ],
                ]
            )
            ->andReturn($this->jsonResponse($payload));

        $user = $this->makeProvider()->setHttpClient($http)->userFromToken('TKN');

        $this->assertSame($payload['user']['id'], $user->getId());
        $this->assertSame('TKN', $user->token);
    }

    public function test_full_user_flow_with_mocked_http()
    {
        $request = $this->requestWithSession([
            'state' => 'matching-state',
            'code' => 'auth-code',
        ]);
        $request->session()->put('state', 'matching-state');

        $http = m::mock(Client::class);
        $http->shouldReceive('post')
            ->once()
            ->with(
                'https://crm.example.test/oauth/access-token',
                m::on(function ($options) {
                    return $options['headers']['Accept'] === 'application/json'
                        && $options['form_params']['grant_type'] === 'authorization_code'
                        && $options['form_params']['client_id'] === 'test-client-id'
                        && $options['form_params']['client_secret'] === 'test-client-secret'
                        && $options['form_params']['code'] === 'auth-code'
                        && $options['form_params']['redirect_uri'] === 'https://app.test/auth/remp/callback';
                })
            )
            ->andReturn($this->jsonResponse([
                'access_token' => 'TKN',
                'refresh_token' => 'RFR',
                'expires_in' => 3600,
            ]));

        $http->shouldReceive('get')
            ->once()
            ->with(
                'https://crm.example.test/api/v1/user/info?source=oauth_token',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer TKN',
                    ],
                ]
            )
            ->andReturn($this->jsonResponse($this->rempUserPayload()));

        $user = $this->makeProvider($request)->setHttpClient($http)->user();

        $this->assertSame(42, $user->getId());
        $this->assertSame('Toni Hofer', $user->getName());
        $this->assertSame('toni@example.test', $user->getEmail());
        $this->assertSame('TKN', $user->token);
        $this->assertSame('RFR', $user->refreshToken);
        $this->assertSame(3600, $user->expiresIn);
    }
}
