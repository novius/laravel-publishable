<?php

namespace Novius\LaravelPublishable\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Novius\LaravelPublishable\Scopes\PublishableScope;

/**
 * @method static static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withNotPublished()
 * @method static static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder onlyNotPublished()
 */
trait Publishable
{
    /**
     * Boot the publishing trait for a model.
     *
     * @return void
     */
    public static function bootPublishable()
    {
        static::addGlobalScope(new PublishableScope);
        static::saving(static function (Model $model) {
            $published_first_at = $model->{$model->getPublishedFirstAtColumn()};
            $published_at = $model->{$model->getPublishedAtColumn()};
            $now = Carbon::now();

            if ((! $published_first_at || $published_first_at > $now) && $published_at) {
                $model->{$model->getPublishedFirstAtColumn()} = $published_at;
            }
            if (! $published_at) {
                $model->{$model->getExpiredAtColumn()} = null;
            }
        });
    }

    /**
     * Initialize the soft deleting trait for an instance.
     *
     * @return void
     */
    public function initializePublishable()
    {
        if (! isset($this->casts[$this->getPublishedAtColumn()])) {
            $this->casts[$this->getPublishedFirstAtColumn()] = 'datetime';
            $this->casts[$this->getPublishedAtColumn()] = 'datetime';
            $this->casts[$this->getExpiredAtColumn()] = 'datetime';
        }
    }

    /**
     * Determine if the model instance has been published.
     */
    public function isPublished(): bool
    {
        $published_at = $this->{$this->getPublishedAtColumn()};

        if (! $published_at) {
            return false;
        }

        $expired_at = $this->{$this->getExpiredAtColumn()};
        $now = Carbon::now();

        return $published_at <= $now && (! $expired_at || $expired_at > $now);
    }

    /**
     * Get the name of the "published first at" column.
     *
     * @return string
     */
    public function getPublishedFirstAtColumn()
    {
        return defined('static::PUBLISHED_FIRST_AT') ? static::PUBLISHED_FIRST_AT : 'published_first_at';
    }

    /**
     * Get the name of the "published at" column.
     *
     * @return string
     */
    public function getPublishedAtColumn()
    {
        return defined('static::PUBLISHED_AT') ? static::PUBLISHED_AT : 'published_at';
    }

    /**
     * Get the name of the "expired at" column.
     *
     * @return string
     */
    public function getExpiredAtColumn()
    {
        return defined('static::EXPIRED_AT') ? static::EXPIRED_AT : 'expired_at';
    }

    /**
     * Get the fully qualified "published first at" column.
     *
     * @return string
     */
    public function getQualifiedPublishedFirstAtColumn()
    {
        return $this->qualifyColumn($this->getPublishedFirstAtColumn());
    }

    /**
     * Get the fully qualified "published at" column.
     *
     * @return string
     */
    public function getQualifiedPublishedAtColumn()
    {
        return $this->qualifyColumn($this->getPublishedAtColumn());
    }

    /**
     * Get the fully qualified "expired at" column.
     *
     * @return string
     */
    public function getQualifiedExpiredAtColumn()
    {
        return $this->qualifyColumn($this->getExpiredAtColumn());
    }
}
