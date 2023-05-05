<?php

namespace Novius\LaravelPublishable\Tests;

use Illuminate\Support\Carbon;
use Spatie\TestTime\TestTime;

class PublishableTest extends TestCase
{
    /* --- Publishable Tests --- */

    /** @test */
    public function a_model_can_be_published_multiple_time()
    {
        $model = PublishableModel::factory()->create();

        $this->assertNull($model->fresh()->published_at);

        $model->published_at = Carbon::now();
        $model->save();

        $this->assertNotNull($model->fresh()->published_first_at);
        $this->assertNotNull($model->fresh()->published_at);

        $model->published_at = null;
        $model->save();

        $this->assertNotNull($model->fresh()->published_first_at);
        $this->assertNull($model->fresh()->published_at);
    }

    /** @test */
    public function a_model_can_be_published_in_the_future_and_only_be_accessed_after_that()
    {
        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 20:30:00');

        $model = PublishableModel::factory()->create();

        $this->assertNull($model->fresh()->published_at);

        $model->published_at = Carbon::parse('2023-05-05 21:00:00');
        $model->save();

        $this->assertCount(0, PublishableModel::all());
        $this->assertEquals('2023-05-05 21:00:00', $model->fresh()->published_at->toDateTimeString());
        $this->assertNull($model->fresh()->expired_at);

        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 21:10:00');

        $this->assertCount(1, PublishableModel::all());
    }

    /** @test */
    public function all_models_can_be_found_with_the_withNotPublished_scope()
    {
        PublishableModel::factory()->published(1)->create();
        PublishableModel::factory()->published(-1)->create();
        PublishableModel::factory()->published(-2)->expired(-1)->create();
        PublishableModel::factory()->create();

        $this->assertCount(1, PublishableModel::all());
        $this->assertCount(4, PublishableModel::withNotPublished()->get());
    }

    /** @test */
    public function only_not_published_models_can_be_found_with_the_onlyNotPublished_scope()
    {
        PublishableModel::factory()->published(1)->create();
        PublishableModel::factory()->published(-1)->create();
        PublishableModel::factory()->published(-2)->expired(1)->create();
        PublishableModel::factory()->published(-2)->expired(-1)->create();
        PublishableModel::factory()->create();

        $this->assertCount(3, PublishableModel::onlyNotPublished()->get());
    }

    /** @test */
    public function a_model_can_be_expired_in_the_future_and_only_be_accessed_before_that()
    {
        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 20:30:00');

        $model = PublishableModel::factory()->published()->create();

        $this->assertNull($model->fresh()->expired_at);

        $model->expired_at = Carbon::parse('2023-05-05 21:00:00');
        $model->save();

        $this->assertCount(1, PublishableModel::all());

        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 21:10:00');

        $this->assertCount(0, PublishableModel::all());
    }

    /** @test */
    public function accessor_isPublished_returns_true_if_published()
    {
        $this->travelTo(Carbon::parse('2023-05-05 20:30:00'));
        $model = PublishableModel::factory()->create([
            'published_at' => Carbon::parse('2023-05-05 21:30:00'),
            'expired_at' => Carbon::parse('2023-05-05 21:40:00'),
        ]);

        $this->assertFalse($model->isPublished());

        $this->travelTo(Carbon::parse('2023-05-05 21:35:00'));
        $this->assertTrue($model->isPublished());

        $this->travelTo(Carbon::parse('2023-05-05 21:45:00'));
        $this->assertFalse($model->isPublished());
    }
}
