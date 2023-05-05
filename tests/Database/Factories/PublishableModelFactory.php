<?php

namespace Novius\LaravelPublishable\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Novius\LaravelPublishable\Tests\PublishableModel;

class PublishableModelFactory extends Factory
{
    protected $model = PublishableModel::class;

    public function expired(int $days = 0)
    {
        return $this->state(function (array $attributes) use ($days) {
            return [
                'expired_at' => now()->addDays($days),
            ];
        });
    }

    public function published(int $days = 0)
    {
        return $this->state(function (array $attributes) use ($days) {
            return [
                'published_at' => now()->addDays($days),
            ];
        });
    }

    public function definition()
    {
        return [];
    }
}
