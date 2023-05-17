<?php

namespace Novius\LaravelPublishable\Tests;

use Illuminate\Support\Carbon;
use Novius\LaravelPublishable\Enums\PublicationStatus;
use Spatie\TestTime\TestTime;

class PublishableTest extends TestCase
{
    /* --- Publishable Tests --- */

    /** @test */
    public function a_model_can_be_published_multiple_time()
    {
        $model = PublishableModel::factory()->create();

        $this->assertEquals($model->fresh()->publication_status, PublicationStatus::draft);
        $this->assertFalse($model->fresh()->isPublished());

        $model->publication_status = PublicationStatus::published;
        $model->save();

        $this->assertNotNull($model->fresh()->published_first_at);
        $this->assertTrue($model->fresh()->isPublished());

        $model->publication_status = PublicationStatus::draft;
        $model->save();

        $this->assertNotNull($model->fresh()->published_first_at);
        $this->assertFalse($model->fresh()->isPublished());
    }

    /** @test */
    public function a_model_can_be_published_in_the_future_and_only_be_accessed_after_that()
    {
        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 20:30:00');

        $model = PublishableModel::factory()->create();

        $this->assertNull($model->fresh()->published_at);

        $model->publication_status = PublicationStatus::scheduled;
        $model->published_at = Carbon::parse('2023-05-05 21:00:00');
        $model->save();

        $this->assertCount(0, PublishableModel::all());
        $this->assertFalse($model->fresh()->isPublished());
        $this->assertEquals('2023-05-05 21:00:00', $model->fresh()->published_at->toDateTimeString());
        $this->assertNull($model->fresh()->expired_at);

        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 21:10:00');

        $this->assertCount(1, PublishableModel::all());
    }

    /** @test */
    public function all_models_can_be_found_with_scopes()
    {
        PublishableModel::factory()->scheduled(1)->create();
        PublishableModel::factory()->scheduled(-1)->create();
        PublishableModel::factory()->scheduled(-2, 1)->create();
        PublishableModel::factory()->scheduled(-2, -1)->create();
        PublishableModel::factory()->published()->create();
        PublishableModel::factory()->draft()->create();
        PublishableModel::factory()->create();

        $this->assertCount(3, PublishableModel::all());
        $this->assertCount(7, PublishableModel::withNotPublished()->get());
        $this->assertCount(4, PublishableModel::onlyNotPublished()->get());
        $this->assertCount(2, PublishableModel::onlyDrafted()->get());
        $this->assertCount(1, PublishableModel::onlyExpired()->get());
        $this->assertCount(1, PublishableModel::onlyWillBePublished()->get());
    }

    /** @test */
    public function a_model_can_be_expired_in_the_future_and_only_be_accessed_before_that()
    {
        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 20:30:00');

        $model = PublishableModel::factory()->scheduled()->create();

        $this->assertNull($model->fresh()->expired_at);
        $this->assertTrue($model->fresh()->isPublished());

        $model->expired_at = Carbon::parse('2023-05-05 21:00:00');
        $model->save();

        $this->assertCount(1, PublishableModel::all());
        $this->assertTrue($model->fresh()->isPublished());

        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 21:10:00');

        $this->assertCount(0, PublishableModel::all());
        $this->assertFalse($model->fresh()->isPublished());
    }

    /** @test */
    public function accessor_isPublished_returns_true_if_published()
    {
        $this->travelTo(Carbon::parse('2023-05-05 20:30:00'));
        $model = PublishableModel::factory()->create([
            'publication_status' => PublicationStatus::scheduled,
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
