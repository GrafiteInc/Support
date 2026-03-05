![Grafite Support](GrafiteSupport-banner.png)

**Support** - A handy set of Support components for Laravel apps.

[![Build Status](https://github.com/GrafiteInc/Support/workflows/php-package-tests.yml/badge.svg?branch=main)](https://github.com/GrafiteInc/Support/actions?query=workflow%3A%22PHP+Package+Tests%22)
[![Maintainability](https://qlty.sh/gh/GrafiteInc/projects/Support/maintainability.svg)](https://qlty.sh/gh/GrafiteInc/projects/Support)
[![Packagist](https://img.shields.io/packagist/dt/grafite/support.svg)](https://packagist.org/packages/grafite/support)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

The Support package is a collection of handy tools for various Laravel components.

##### Author(s):
* [Matt Lantz](https://github.com/mlantz) ([@mattylantz](http://twitter.com/mattylantz), mattlantz at gmail dot com)

## Requirements

1. PHP 8.1+

## Compatibility and Support

| Laravel Version | Package Tag | Supported |
|-----------------|-------------|-----------|
| ^9.x - ^12.x | 1.x | yes |

### Installation

Start a new Laravel project:
```php
composer create-project laravel/laravel your-project-name
```

Then run the following to add Support
```php
composer require "grafite/support"
```

Time to publish those assets!
```php
php artisan vendor:publish --provider="Grafite\Support\SupportProvider"
```

## Documentation

[https://documentation.grafite.ca/docs/utilities-support](https://documentation.grafite.ca/docs/utilities-support)

## License
Support is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

### Bug Reporting and Feature Requests
Please add as many details as possible regarding submission of issues and feature requests

### Disclaimer
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
