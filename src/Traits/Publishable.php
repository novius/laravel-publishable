<?php

namespace Novius\LaravelPublishable\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Novius\LaravelPublishable\Enums\PublicationStatus;

/**
 * @method static static|Builder|\Illuminate\Database\Query\Builder published()
 * @method static static|Builder|\Illuminate\Database\Query\Builder notPublished()
 * @method static static|Builder|\Illuminate\Database\Query\Builder onlyDrafted()
 * @method static static|Builder|\Illuminate\Database\Query\Builder onlyExpired()
 * @method static static|Builder|\Illuminate\Database\Query\Builder onlyWillBePublished()
 */
trait Publishable
{
    /**
     * Boot the publishing trait for a model.
     */
    public static function bootPublishable(): void
    {
        static::saving(static function (Model $model) {
            /** @var Model|Publishable $model */
            $publication_status = $model->{$model->getPublicationStatusColumn()};
            $published_first_at = $model->{$model->getPublishedFirstAtColumn()};
            $now = Carbon::now();

            if ($published_first_at !== null && in_array($publication_status, [PublicationStatus::draft, PublicationStatus::unpublished], true)) {
                $model->{$model->getPublicationStatusColumn()} = PublicationStatus::unpublished;
                $model->{$model->getPublishedAtColumn()} = null;
                $model->{$model->getExpiredAtColumn()} = $now;
            } elseif ($published_first_at === null && in_array($publication_status, [PublicationStatus::draft, PublicationStatus::unpublished], true)) {
                $model->{$model->getPublicationStatusColumn()} = PublicationStatus::draft;
                $model->{$model->getPublishedAtColumn()} = null;
                $model->{$model->getExpiredAtColumn()} = null;
            } elseif ($publication_status === PublicationStatus::published) {
                $model->{$model->getPublishedAtColumn()} = null;
                $model->{$model->getExpiredAtColumn()} = null;

                if ($published_first_at === null) {
                    $model->{$model->getPublishedFirstAtColumn()} = $now;
                }
            } elseif ($publication_status === PublicationStatus::scheduled) {
                $published_at = $model->{$model->getPublishedAtColumn()};
                if ($published_at === null) {
                    $model->{$model->getPublishedAtColumn()} = $now;
                    $published_at = $now;
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

    public function willBePublished(): bool
    {
        $publication_status = $this->{$this->getPublicationStatusColumn()};

        if ($publication_status === PublicationStatus::scheduled) {
            $published_at = $this->{$this->getPublishedAtColumn()};

            if (! $published_at) {
                return false;
            }

            $now = Carbon::now();

            return $published_at > $now;
        }

        return $publication_status === PublicationStatus::published;
    }

    /**
     * Return a label for the current publication status
     */
    public function publicationLabel(): string
    {
        if (in_array($this->{$this->getPublicationStatusColumn()}, [PublicationStatus::draft, PublicationStatus::published], true)) {
            return $this->{$this->getPublicationStatusColumn()}->getLabel();
        }

        $published_at = $this->{$this->getPublishedAtColumn()};
        if ($this->{$this->getPublicationStatusColumn()} === PublicationStatus::scheduled) {
            $published_at = $this->{$this->getPublishedAtColumn()};
            if ($published_at === null) {
                return PublicationStatus::draft->getLabel();
            }
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
     */
    public function getPublicationStatusColumn(): string
    {
        return defined('static::PUBLICATION_STATUS') ? static::PUBLICATION_STATUS : 'publication_status';
    }

    /**
     * Get the name of the "published first at" column.
     */
    public function getPublishedFirstAtColumn(): string
    {
        return defined('static::PUBLISHED_FIRST_AT') ? static::PUBLISHED_FIRST_AT : 'published_first_at';
    }

    /**
     * Get the name of the "published at" column.
     */
    public function getPublishedAtColumn(): string
    {
        return defined('static::PUBLISHED_AT') ? static::PUBLISHED_AT : 'published_at';
    }

    /**
     * Get the name of the "expired at" column.
     */
    public function getExpiredAtColumn(): string
    {
        return defined('static::EXPIRED_AT') ? static::EXPIRED_AT : 'expired_at';
    }

    /**
     * Get the fully qualified "publication status" column.
     */
    public function getQualifiedPublicationStatusColumn(): string
    {
        return $this->qualifyColumn($this->getPublicationStatusColumn());
    }

    /**
     * Get the fully qualified "published first at" column.
     */
    public function getQualifiedPublishedFirstAtColumn(): string
    {
        return $this->qualifyColumn($this->getPublishedFirstAtColumn());
    }

    /**
     * Get the fully qualified "published at" column.
     */
    public function getQualifiedPublishedAtColumn(): string
    {
        return $this->qualifyColumn($this->getPublishedAtColumn());
    }

    /**
     * Get the fully qualified "expired at" column.
     */
    public function getQualifiedExpiredAtColumn(): string
    {
        return $this->qualifyColumn($this->getExpiredAtColumn());
    }

    public function scopePublished(Builder $builder): void
    {
        $builder->where($this->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::published)
            ->orWhere(function (Builder $builder) {
                $builder->where($this->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::scheduled)
                    ->whereNotNull($this->getQualifiedPublishedAtColumn())
                    ->where($this->getQualifiedPublishedAtColumn(), '<=', now()->toDateTimeString())
                    ->where(function (Builder $builder) {
                        $builder->whereNull($this->getQualifiedExpiredAtColumn())
                            ->orWhere($this->getQualifiedExpiredAtColumn(), '>', now()->toDateTimeString());
                    });
            });
    }

    public function scopeNotPublished(Builder $builder): void
    {
        $builder->whereIn($this->getQualifiedPublicationStatusColumn(), [PublicationStatus::draft, PublicationStatus::unpublished])
            ->orWhere(function (Builder $builder) {
                $builder->where($this->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::scheduled)
                    ->where(function (Builder $builder) {
                        $builder->whereNull($this->getQualifiedPublishedAtColumn())
                            ->orWhere($this->getQualifiedPublishedAtColumn(), '>', now()->toDateTimeString())
                            ->orWhere(function (Builder $builder) {
                                $builder->whereNotNull($this->getQualifiedExpiredAtColumn())
                                    ->where($this->getQualifiedExpiredAtColumn(), '<=', now()->toDateTimeString());
                            });
                    });
            });
    }

    public function scopeOnlyDrafted(Builder $builder): void
    {
        $builder->where($this->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::draft);
    }

    public function scopeOnlyExpired(Builder $builder): void
    {
        $builder->where($this->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::unpublished)
            ->orWhere(function (Builder $builder) {
                $builder->where($this->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::scheduled)
                    ->whereNotNull($this->getQualifiedExpiredAtColumn())
                    ->where($this->getQualifiedExpiredAtColumn(), '<=', now()->toDateTimeString());
            });
    }

    public function scopeOnlyWillBePublished(Builder $builder): void
    {
        $builder->where($this->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::scheduled)
            ->whereNotNull($this->getQualifiedPublishedAtColumn())
            ->where($this->getQualifiedPublishedAtColumn(), '>', now()->toDateTimeString());
    }
}
