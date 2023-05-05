<?php

namespace Novius\LaravelPublishable\Tests;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Novius\LaravelPublishable\Traits\Publishable;

class PublishableModel extends Model
{
    use HasFactory;
    use HasTimestamps;
    use Publishable;

    protected $table = 'publishable_models';

    protected $guarded = [];
}
