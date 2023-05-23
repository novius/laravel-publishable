<?php

namespace Novius\LaravelPublishable\Enums;

enum PublicationStatus: string
{
    case draft = 'draft';
    case published = 'published';
    case unpublished = 'unpublished';
    case scheduled = 'scheduled';

    public function getLabel(): string
    {
        return trans('publishable::messages.status.'.str($this->value));
    }
}
