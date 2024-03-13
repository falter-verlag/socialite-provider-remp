# REMP Provider for Laravel Socialite

This package provides REMP OAuth 2.0 support for [Laravel Socialite](https://laravel.com/docs/socialite).

## Installation

To install, add the repository to your `composer.json` file:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/falter-verlag/socialite-provider-remp"
        }
    ]
}
```

After adding the repository, you can install the package using composer:

```
composer require falter-verlag/socialite-provider-remp
```

## Usage

You can use the provider in the same way as any other Socialite provider. First, you need to add the provider to your `config/services.php` file:

```php
'remp' => [
    'client_id' => env('REMP_CLIENT_ID'),
    'client_secret' => env('REMP_CLIENT_SECRET'),
    'redirect' => env('REMP_REDIRECT_URI'),
    'remp_url' => env('REMP_URL'), // The URL of the REMP instance, e.g. https://crm.press
],
```

Then, you can use the provider in your application:

```php
use Laravel\Socialite\Facades\Socialite;

// Redirect the user to the REMP authentication page
return Socialite::driver('remp')->redirect();

// Handle the callback from REMP
$user = Socialite::driver('remp')->user();
```

For more information on using Socialite, please refer to the [official documentation](https://laravel.com/docs/socialite).

## License

The MIT License (MIT). Please see License File for more information.
