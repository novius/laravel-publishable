<?php

namespace Novius\LaravelPublishable\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Novius\LaravelPublishable\Enums\PublicationStatus;
use Novius\LaravelPublishable\Tests\Models\PublishableModel;

class PublishableModelFactory extends Factory
{
    protected $model = PublishableModel::class;

    public function draft()
    {
        return $this->state(function (array $attributes) {
            return [
                'publication_status' => PublicationStatus::draft,
            ];
        });
    }

    public function published()
    {
        return $this->state(function (array $attributes) {
            return [
                'publication_status' => PublicationStatus::published,
            ];
        });
    }

    public function unpublished(int $first_published_days = -2, int $expired_days = -1)
    {
        return $this->state(function (array $attributes) use ($first_published_days, $expired_days) {
            return [
                'publication_status' => PublicationStatus::unpublished,
                'published_first_at' => now()->addDays($first_published_days),
                'expired_at' => now()->addDays($expired_days),
            ];
        });
    }

    public function scheduled(int $published_days = 0, int $expired_days = null)
    {
        return $this->state(function (array $attributes) use ($published_days, $expired_days) {
            return [
                'publication_status' => PublicationStatus::scheduled,
                'published_at' => now()->addDays($published_days),
                'expired_at' => $expired_days !== null ? now()->addDays($expired_days) : null,
            ];
        });
    }

    public function definition()
    {
        return [];
    }
}
