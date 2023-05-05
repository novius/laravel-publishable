<?php

namespace Novius\LaravelPublishable;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class LaravelPublishableServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    protected function configureMacros()
    {
        Blueprint::macro('publishable', function ($columnPublishedFirstAt = 'published_first_at', $columnPublishedAt = 'published_at', $columnExpiredAt = 'expired_at') {
            $this->timestamp($columnPublishedFirstAt)->nullable()->index();
            $this->timestamp($columnPublishedAt)->nullable()->index();
            $this->timestamp($columnExpiredAt)->nullable()->index();
        });
    }

    public function boot()
    {
        $this->configureMacros();
    }
}
