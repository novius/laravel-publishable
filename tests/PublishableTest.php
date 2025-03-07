<?php

namespace Novius\LaravelPublishable\Tests;

use Illuminate\Support\Carbon;
use Novius\LaravelPublishable\Enums\PublicationStatus;
use Novius\LaravelPublishable\Tests\Models\PublishableModel;
use Spatie\TestTime\TestTime;

class PublishableTest extends TestCase
{
    /* --- Publishable Tests --- */

    /** @test */
    public function a_model_can_be_published_multiple_time(): void: void
    {
        $model = PublishableModel::factory()->create();

        $this->assertEquals(PublicationStatus::draft, $model->fresh()->publication_status);
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
    public function a_model_can_be_published_in_the_future_and_only_be_accessed_after_that(): void
    {
        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 20:30:00');

        $model = PublishableModel::factory()->create();

        $this->assertNull($model->fresh()->published_at);

        $model->publication_status = PublicationStatus::scheduled;
        $model->published_at = Carbon::parse('2023-05-05 21:00:00');
        $model->save();

        $this->assertCount(1, PublishableModel::all());
        $this->assertCount(0, PublishableModel::published()->get());
        $this->assertFalse($model->fresh()->isPublished());
        $this->assertEquals('2023-05-05 21:00:00', $model->fresh()->published_at->toDateTimeString());
        $this->assertNull($model->fresh()->expired_at);

        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 21:10:00');

        $this->assertCount(1, PublishableModel::all());
    }

    /** @test */
    public function all_models_can_be_found_with_scopes(): void
    {
        PublishableModel::factory()->scheduled(1)->create();
        PublishableModel::factory()->scheduled(-1)->create();
        PublishableModel::factory()->scheduled(-2, 1)->create();
        PublishableModel::factory()->scheduled(-2, -1)->create();
        PublishableModel::factory()->published()->create();
        PublishableModel::factory()->unpublished()->create();
        PublishableModel::factory()->draft()->create();
        PublishableModel::factory()->create();

        $this->assertCount(8, PublishableModel::all());
        $this->assertCount(3, PublishableModel::published()->get());
        $this->assertCount(5, PublishableModel::notPublished()->get());
        $this->assertCount(2, PublishableModel::onlyDrafted()->get());
        $this->assertCount(2, PublishableModel::onlyExpired()->get());
        $this->assertCount(1, PublishableModel::onlyWillBePublished()->get());
    }

    /** @test */
    public function a_model_can_be_expired_in_the_future_and_only_be_accessed_before_that(): void
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

        $this->assertCount(1, PublishableModel::all());
        $this->assertCount(0, PublishableModel::published()->get());
        $this->assertFalse($model->fresh()->isPublished());
    }

    /** @test */
    public function only_change_status_of_a_model(): void
    {
        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 19:30:00');

        $model = PublishableModel::factory()->draft()->create();

        $this->assertSame($model->fresh()->publication_status, PublicationStatus::draft);
        $this->assertNull($model->fresh()->published_first_at);
        $this->assertNull($model->fresh()->published_at);
        $this->assertNull($model->fresh()->expired_at);
        $this->assertFalse($model->fresh()->isPublished());
        $this->assertFalse($model->fresh()->willBePublished());

        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 20:30:00');

        $model->publication_status = PublicationStatus::published;
        $model->save();

        $this->assertSame($model->fresh()->publication_status, PublicationStatus::published);
        $this->assertNotNull($model->fresh()->published_first_at);
        $this->assertNull($model->fresh()->published_at);
        $this->assertNull($model->fresh()->expired_at);
        $this->assertTrue($model->fresh()->isPublished());
        $this->assertTrue($model->fresh()->willBePublished());

        /** @var Carbon $published_first_at */
        $published_first_at = $model->fresh()->published_first_at;

        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 21:30:00');

        $model->publication_status = PublicationStatus::unpublished;
        $model->save();

        $this->assertSame($model->fresh()->publication_status, PublicationStatus::unpublished);
        $this->assertTrue($published_first_at->equalTo($model->fresh()->published_first_at));
        $this->assertNull($model->fresh()->published_at);
        $this->assertNotNull($model->fresh()->expired_at);
        $this->assertFalse($model->fresh()->isPublished());
        $this->assertFalse($model->fresh()->willBePublished());

        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 22:30:00');

        $model->publication_status = PublicationStatus::scheduled;
        $model->expired_at = null;
        $model->save();

        $this->assertSame($model->fresh()->publication_status, PublicationStatus::scheduled);
        $this->assertTrue($published_first_at->equalTo($model->fresh()->published_first_at));
        $this->assertNotNull($model->fresh()->published_at);
        $this->assertNull($model->fresh()->expired_at);
        $this->assertTrue($model->fresh()->isPublished());
        $this->assertFalse($model->fresh()->willBePublished());

        TestTime::freeze('Y-m-d H:i:s', '2023-05-05 23:30:00');

        $model->publication_status = PublicationStatus::scheduled;
        $model->published_at = Carbon::parse('2023-05-06 21:30:00');
        $model->expired_at = Carbon::parse('2023-05-07 21:30:00');
        $model->save();

        $this->assertSame($model->fresh()->publication_status, PublicationStatus::scheduled);
        $this->assertTrue($published_first_at->equalTo($model->fresh()->published_first_at));
        $this->assertTrue(Carbon::parse('2023-05-06 21:30:00')->equalTo($model->fresh()->published_at));
        $this->assertTrue(Carbon::parse('2023-05-07 21:30:00')->equalTo($model->fresh()->expired_at));
        $this->assertFalse($model->fresh()->isPublished());
        $this->assertTrue($model->fresh()->willBePublished());

        $this->travelTo(Carbon::parse('2023-05-6 22:30:00'));

        $this->assertTrue($model->fresh()->isPublished());
        $this->assertFalse($model->fresh()->willBePublished());

        $this->travelTo(Carbon::parse('2023-05-7 22:30:00'));

        $this->assertFalse($model->fresh()->isPublished());
        $this->assertFalse($model->fresh()->willBePublished());
    }

    /** @test */
    public function accessor_is_published_returns_true_if_published(): void
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
