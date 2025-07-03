# Laravel Publishable

[![Novius CI](https://github.com/novius/laravel-publishable/actions/workflows/main.yml/badge.svg?branch=main)](https://github.com/novius/laravel-publishable/actions/workflows/main.yml)
[![Packagist Release](https://img.shields.io/packagist/v/novius/laravel-nova-publishable.svg?maxAge=1800&style=flat-square)](https://packagist.org/packages/novius/laravel-nova-publishable)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](http://www.gnu.org/licenses/agpl-3.0)


## Introduction

A package for making Laravel Eloquent models "publishable" using 4 states : draft, published, unpublished and scheduled.
Manage an additional `published_first_at` date for order by and display.

## Requirements

* Laravel >= 10.0
* PHP >= 8.2

> **NOTE**: These instructions are for Laravel >= 10.0 and PHP >= 8.2 If you are using prior version, please
> see the [previous version's docs](https://github.com/novius/laravel-publishable/tree/2.x).


## Installation

You can install the package via composer:

```bash
composer require novius/laravel-publishable
```

```bash
php artisan vendor:publish --provider="Novius\Publishable\LaravelPublishableServiceProvider" --tag=lang
```

## Usage

#### Migrations

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('text');
    $table->timestamps();
    $table->publishable(); // Macro provided by the package
});
```

#### Eloquent Model Trait

```php
namespace App\Models;

use \Illuminate\Database\Eloquent\Model;
use \Novius\LaravelPublishable\Publishable;

class Post extends Model {
    use Publishable;
    ...
}
```

#### Extensions

The extensions shipped with this trait include; `notPublished`, `published`, `onlyDrafted`, `onlyExpired`, `onlyWillBePublished` and can be used accordingly:

```php
$post = Post::first();
$post->isPublished();

$postsPublished = Post::all();
$postsPublished = Post::query()->published();
$onlyNotPublishedPosts = Post::query()->notPublished();
$onlyDraftedPosts = Post::query()->onlyDrafted();
$onlyExpiredPosts = Post::query()->onlyExpired();
$onlyWillBePublishedPosts = Post::query()->onlyWillBePublished();
```

### Testing

```bash
composer run test
```

## CS Fixer

Lint your code with Laravel Pint using:

```bash
composer run cs-fix
```

## Licence

This package is under [GNU Affero General Public License v3](http://www.gnu.org/licenses/agpl-3.0.html) or (at your option) any later version.
