<?php

namespace Novius\LaravelPublishable;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;
use Novius\LaravelPublishable\Enums\PublicationStatus;

class LaravelPublishableServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureMacros();
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'publishable');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/publishable'),
        ]);
    }

    protected function configureMacros(): void
    {
        Blueprint::macro('publishable', function ($columnStatus = 'publication_status', $columnPublishedFirstAt = 'published_first_at', $columnPublishedAt = 'published_at', $columnExpiredAt = 'expired_at') {
            $this->enum($columnStatus, array_column(PublicationStatus::cases(), 'value'))->default(PublicationStatus::draft->value);
            $this->timestamp($columnPublishedFirstAt)->nullable()->index();
            $this->timestamp($columnPublishedAt)->nullable();
            $this->timestamp($columnExpiredAt)->nullable();

            $this->index([$columnStatus, $columnPublishedAt, $columnExpiredAt], $this->getTable().'_publishable');
        });

        Blueprint::macro('dropPublishable', function ($columnStatus = 'publication_status', $columnPublishedFirstAt = 'published_first_at', $columnPublishedAt = 'published_at', $columnExpiredAt = 'expired_at') {
            $this->dropColumn([$columnStatus, $columnPublishedFirstAt, $columnPublishedAt, $columnExpiredAt]);
        });
    }
}
