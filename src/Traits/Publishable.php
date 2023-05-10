<?php

namespace Novius\LaravelPublishable\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Novius\LaravelPublishable\Enums\PublicationStatus;
use Novius\LaravelPublishable\Scopes\PublishableScope;

/**
 * @method static static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withNotPublished()
 * @method static static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withoutNotPublished()
 * @method static static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder onlyNotPublished()
 */
trait Publishable
{
    /**
     * Boot the publishing trait for a model.
     */
    public static function bootPublishable(): void
    {
        static::addGlobalScope(new PublishableScope);
        static::saving(static function (Model $model) {
            $publication_status = $model->{$model->getPublicationStatusColumn()};
            $published_first_at = $model->{$model->getPublishedFirstAtColumn()};
            $now = Carbon::now();

            if (in_array($publication_status, [PublicationStatus::draft, PublicationStatus::published])) {
                $model->{$model->getPublishedAtColumn()} = null;
                $model->{$model->getExpiredAtColumn()} = null;

                if ($publication_status === PublicationStatus::published && $published_first_at === null) {
                    $model->{$model->getPublishedFirstAtColumn()} = $now;
                }
            } else {
                $published_at = $model->{$model->getPublishedAtColumn()};
                if ($published_at === null) {
                    $model->{$model->getPublishedAtColumn()} = $now;
                }

                if ($published_first_at === null || $published_first_at > $published_at) {
                    $model->{$model->getPublishedFirstAtColumn()} = $published_at;
                }
            }
        });
    }

    /**
     * Initialize the soft deleting trait for an instance.
     */
    public function initializePublishable(): void
    {
        if (! isset($this->casts[$this->getPublicationStatusColumn()])) {
            $this->casts[$this->getPublicationStatusColumn()] = PublicationStatus::class;
        }
        if (! isset($this->casts[$this->getPublishedFirstAtColumn()])) {
            $this->casts[$this->getPublishedFirstAtColumn()] = 'datetime';
        }
        if (! isset($this->casts[$this->getPublishedAtColumn()])) {
            $this->casts[$this->getPublishedAtColumn()] = 'datetime';
        }
        if (! isset($this->casts[$this->getExpiredAtColumn()])) {
            $this->casts[$this->getExpiredAtColumn()] = 'datetime';
        }
        if (! isset($this->attributes[$this->getPublicationStatusColumn()])) {
            $this->attributes[$this->getPublicationStatusColumn()] = PublicationStatus::draft;
        }
    }

    /**
     * Determine if the model instance has been published.
     */
    public function isPublished(): bool
    {
        $publication_status = $this->{$this->getPublicationStatusColumn()};

        if ($publication_status === PublicationStatus::scheduled) {
            $published_at = $this->{$this->getPublishedAtColumn()};

            if (! $published_at) {
                return false;
            }

            $expired_at = $this->{$this->getExpiredAtColumn()};
            $now = Carbon::now();

            return $published_at <= $now && (! $expired_at || $expired_at > $now);
        }

        return $publication_status === PublicationStatus::published;
    }

    /**
     * Return a label for the current publication status
     */
    public function publicationLabel(): string
    {
        if (in_array($this->{$this->getPublicationStatusColumn()}, [PublicationStatus::draft, PublicationStatus::published])) {
            return $this->{$this->getPublicationStatusColumn()}->getLabel();
        }

        $published_at = $this->{$this->getPublishedAtColumn()};
        if ($published_at === null) {
            return PublicationStatus::draft->getLabel();
        }

        $expired_at = $this->{$this->getExpiredAtColumn()};
        $now = Carbon::now();
        if ($expired_at !== null && $expired_at < $now) {
            return trans('publishable::messages.labels.unpublished_since', ['since' => $expired_at]);
        }

        if ($published_at <= $now && $expired_at === null) {
            return trans('publishable::messages.labels.published_since', ['since' => $published_at]);
        }

        if ($published_at <= $now && $expired_at !== null) {
            return trans('publishable::messages.labels.published_since_until', ['since' => $published_at, 'until' => $expired_at]);
        }

        if ($published_at > $now && $expired_at === null) {
            return trans('publishable::messages.labels.will_be_published_from', ['from' => $published_at]);
        }

        if ($published_at > $now && $expired_at !== null) {
            return trans('publishable::messages.labels.will_be_published_from_to', ['from' => $published_at, 'to' => $expired_at]);
        }

        return PublicationStatus::scheduled->getLabel();
    }

    /**
     * Get the name of the "publication status" column.
     *
     * @return string
     */
    public function getPublicationStatusColumn()
    {
        return defined('static::PUBLICATION_STATUS') ? static::PUBLICATION_STATUS : 'publication_status';
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
     * Get the fully qualified "publication status" column.
     *
     * @return string
     */
    public function getQualifiedPublicationStatusColumn()
    {
        return $this->qualifyColumn($this->getPublicationStatusColumn());
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
