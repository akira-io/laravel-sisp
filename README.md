# This is a laravel package to handle SISP payment

[![Latest Version on Packagist](https://img.shields.io/packagist/v/akira/laravel-sisp.svg)](https://packagist.org/packages/akira/laravel-sisp)
[![Total Downloads](https://img.shields.io/packagist/dt/akira/laravel-sisp.svg)](https://packagist.org/packages/akira/laravel-sisp)
[![PHPStan Level](https://img.shields.io/badge/phpstan-level%209-brightgreen.svg)](https://phpstan.org)
[![License](https://img.shields.io/packagist/l/akira/laravel-sisp.svg)](https://github.com/akira-io/laravel-sisp/blob/main/LICENSE)

This package allows you to easily handle payments through the SISP Payment Gateway, enabling seamless payment processing
within your application. Whether you’re building an e-commerce platform or handling subscription services, Laravel SISP
is designed to simplify your payment workflows.

## Requirements

- PHP 8.4 or higher
- Laravel 11 or higher

## Installation

You can install the package via composer:

```bash
composer require akira/laravel-sisp
```

You can publish and run the migrations with:

```bash
php artisan laravel-sisp:install
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="sisp-config"
```

You can publish the migrations with:

```bash
php artisan vendor:publish --tag="sisp-migrations"
```

[//]: # (Optionally, you can publish the views using)

[//]: # ()

[//]: # (```bash)

[//]: # (php artisan vendor:publish --tag="laravel-sisp-views")

[//]: # (```)

## Documentation

You'll find installation instructions and full documentation on [Akira Sisp website](https://sisp.akira-io.com).

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Kidiatoliny](https://github.com/kidiatoliny)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
