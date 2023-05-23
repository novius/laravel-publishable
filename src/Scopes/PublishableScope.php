<?php

namespace Novius\LaravelPublishable\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Novius\LaravelPublishable\Enums\PublicationStatus;

class PublishableScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     */
    protected array $extensions = [
        'WithNotPublished',
        'WithoutNotPublished',
        'OnlyNotPublished',
        'OnlyDrafted',
        'OnlyExpired',
        'OnlyWillBePublished',
    ];

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::published)
            ->orWhere(function (Builder $builder) use ($model) {
                $builder->where($model->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::scheduled)
                    ->whereNotNull($model->getQualifiedPublishedAtColumn())
                    ->where($model->getQualifiedPublishedAtColumn(), '<=', now()->toDateTimeString())
                    ->where(function (Builder $builder) use ($model) {
                        $builder->whereNull($model->getQualifiedExpiredAtColumn())
                            ->orWhere($model->getQualifiedExpiredAtColumn(), '>', now()->toDateTimeString());
                    });
            });
    }

    /**
     * Extend the query builder with the needed functions.
     */
    public function extend(Builder $builder): void
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    /**
     * Get the "publication status" column for the builder.
     */
    protected function getPublicationStatusColumn(Builder $builder): string
    {
        if (count((array) $builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedPublicationStatusColumn();
        }

        return $builder->getModel()->getPublicationStatusColumn();
    }

    /**
     * Get the "published at" column for the builder.
     */
    protected function getPublishedAtColumn(Builder $builder): string
    {
        if (count((array) $builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedPublishedAtColumn();
        }

        return $builder->getModel()->getPublishedAtColumn();
    }

    /**
     * Get the "expired at" column for the builder.
     */
    protected function getExpiredAtColumn(Builder $builder): string
    {
        if (count((array) $builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedExpiredAtColumn();
        }

        return $builder->getModel()->getExpiredAtColumn();
    }

    /**
     * Add the with-notpublished extension to the builder.
     */
    protected function addWithNotPublished(Builder $builder): void
    {
        $builder->macro('withNotPublished', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the without-notpublished extension to the builder.
     */
    protected function addWithoutNotPublished(Builder $builder): void
    {
        $builder->macro('withoutNotPublished', function (Builder $builder) {
            $model = $builder->getModel();

            return $builder->withoutGlobalScope($this)
                ->where($model->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::published)
                ->orWhere(function (Builder $builder) use ($model) {
                    $builder->where($model->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::scheduled)
                        ->whereNotNull($model->getQualifiedPublishedAtColumn())
                        ->where($model->getQualifiedPublishedAtColumn(), '<=', now()->toDateTimeString())
                        ->where(function (Builder $builder) use ($model) {
                            $builder->whereNull($model->getQualifiedExpiredAtColumn())
                                ->orWhere($model->getQualifiedExpiredAtColumn(), '>', now()->toDateTimeString());
                        });
                });
        });
    }

    /**
     * Add the only-notpublished extension to the builder.
     */
    protected function addOnlyNotPublished(Builder $builder): void
    {
        $builder->macro('onlyNotPublished', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)
                ->whereIn($model->getQualifiedPublicationStatusColumn(), [PublicationStatus::draft, PublicationStatus::unpublished])
                ->orWhere(function (Builder $builder) use ($model) {
                    $builder->where($model->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::scheduled)
                        ->where(function (Builder $builder) use ($model) {
                            $builder->whereNull($model->getQualifiedPublishedAtColumn())
                                ->orWhere($model->getQualifiedPublishedAtColumn(), '>', now()->toDateTimeString())
                                ->orWhere(function (Builder $builder) use ($model) {
                                    $builder->whereNotNull($model->getQualifiedExpiredAtColumn())
                                        ->where($model->getQualifiedExpiredAtColumn(), '<=', now()->toDateTimeString());
                                });
                        });
                });

            return $builder;
        });
    }

    /**
     * Add the only-drafted extension to the builder.
     */
    protected function addOnlyDrafted(Builder $builder): void
    {
        $builder->macro('onlyDrafted', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)
                ->where($model->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::draft);

            return $builder;
        });
    }

    /**
     * Add the only-expired extension to the builder.
     */
    protected function addOnlyExpired(Builder $builder): void
    {
        $builder->macro('onlyExpired', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)
                ->where($model->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::unpublished)
                ->orWhere(function (Builder $builder) use ($model) {
                    $builder->where($model->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::scheduled)
                        ->whereNotNull($model->getQualifiedExpiredAtColumn())
                        ->where($model->getQualifiedExpiredAtColumn(), '<=', now()->toDateTimeString());
                });

            return $builder;
        });
    }

    /**
     * Add the only-willBePublished extension to the builder.
     */
    protected function addOnlyWillBePublished(Builder $builder): void
    {
        $builder->macro('onlyWillBePublished', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)
                ->where($model->getQualifiedPublicationStatusColumn(), '=', PublicationStatus::scheduled)
                ->whereNotNull($model->getQualifiedPublishedAtColumn())
                ->where($model->getQualifiedPublishedAtColumn(), '>', now()->toDateTimeString());

            return $builder;
        });
    }
}
