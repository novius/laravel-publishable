# Laravel Publishable

[![Novius CI](https://github.com/novius/laravel-publishable/actions/workflows/main.yml/badge.svg?branch=main)](https://github.com/novius/laravel-publishable/actions/workflows/main.yml)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](http://www.gnu.org/licenses/agpl-3.0)


## Introduction

A package for making Laravel Eloquent models "publishable" using 3 states : draft, published and scheduled.
Manage an additional `published_first_at` date for order by and display.
Not published models are excluded from queries by default but can be queried via extra scope.

## Requirements

* Laravel 8.0, 9.0 or 10.0

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

The extensions shipped with this trait include; `WithoutNotPublished`, `withNotPublished`, `onlyPublished`, `onlyDrafted`, `onlyExpired`, `onlyWillBePublished` and can be used accordingly:

```php
$post = Post::first();
$post->isPublished();

$postsPublished = Post::all();
$postsPublished = Post::query()->WithoutNotPublished();
$postsWithNotPublished = Post::query()->withNotPublished();
$onlyNotPublishedPosts = Post::query()->onlyNotPublished();
$onlyDraftedPosts = Post::query()->onlyDrafted();
$onlyExpiredPosts = Post::query()->onlyExpired();
$onlyWillBePublishedPosts = Post::query()->onlyWillBePublished();
```

When not specifing any additional scopes, all not published models are excluded from the query by default to prevent leaks of not published data.

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
