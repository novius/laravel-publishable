<?php

namespace Novius\LaravelPublishable\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PublishableScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var array
     */
    protected $extensions = [
        'WithNotPublished',
        'OnlyNotPublished',
    ];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (is_callable([$model, 'getQualifiedPublishedAtColumn'], true, $name)) {
            $builder->whereNotNull($model->getQualifiedPublishedAtColumn())
                ->where($model->getQualifiedPublishedAtColumn(), '<=', now()->toDateTimeString());
        }
        if (is_callable([$model, 'getQualifiedExpiredAtColumn'], true, $name)) {
            $builder->where(function (Builder $builder) use ($model) {
                $builder->whereNull($model->getQualifiedExpiredAtColumn())
                    ->orWhere($model->getQualifiedExpiredAtColumn(), '>', now()->toDateTimeString());
            });
        }
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    /**
     * Get the "published at" column for the builder.
     *
     * @return string
     */
    protected function getPublishedAtColumn(Builder $builder)
    {
        if (count((array) $builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedPublishedAtColumn();
        }

        return $builder->getModel()->getPublishedAtColumn();
    }

    /**
     * Get the "expired at" column for the builder.
     *
     * @return string
     */
    protected function getExpiredAtColumn(Builder $builder)
    {
        if (count((array) $builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedExpiredAtColumn();
        }

        return $builder->getModel()->getExpiredAtColumn();
    }

    /**
     * Add the with-notpublished extension to the builder.
     *
     * @return void
     */
    protected function addWithNotPublished(Builder $builder)
    {
        $builder->macro('withNotPublished', function (Builder $builder, $withNotPublished = true) {
            if (! $withNotPublished) {
                return $builder->withoutNotPublished();
            }

            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the without-notpublished extension to the builder.
     *
     * @return void
     */
    protected function addWithoutNotPublished(Builder $builder)
    {
        $builder->macro('withoutNotPublished', function (Builder $builder) {
            $model = $builder->getModel();

            return $builder->withoutGlobalScope($this)
                ->whereNotNull($model->getQualifiedPublishedAtColumn())
                ->where($model->getQualifiedPublishedAtColumn(), '<=', now()->toDateTimeString())
                ->where(function (Builder $builder) use ($model) {
                    $builder->whereNull($model->getQualifiedExpiredAtColumn())
                        ->orWhere($model->getQualifiedExpiredAtColumn(), '>', now()->toDateTimeString());
                });
        });
    }

    /**
     * Add the only-notpublished extension to the builder.
     *
     * @return void
     */
    protected function addOnlyNotPublished(Builder $builder)
    {
        $builder->macro('onlyNotPublished', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)
                ->whereNull($model->getQualifiedPublishedAtColumn())
                ->orWhere($model->getQualifiedPublishedAtColumn(), '>', now()->toDateTimeString())
                ->orWhere(function (Builder $builder) use ($model) {
                    $builder->whereNotNull($model->getQualifiedExpiredAtColumn())
                        ->where($model->getQualifiedExpiredAtColumn(), '<=', now()->toDateTimeString());
                });

            return $builder;
        });
    }
}
