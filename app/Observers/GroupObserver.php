<?php

namespace App\Observers;

use App\Models\EGroup;
use App\Services\CacheInvalidationService;

class GroupObserver
{
    public function created(EGroup $group): void
    {
        CacheInvalidationService::invalidate('groups', cascade: true);
    }

    public function updated(EGroup $group): void
    {
        CacheInvalidationService::invalidate('groups', cascade: true);
        CacheInvalidationService::invalidateKey("groups:id:{$group->id}");
    }

    public function deleted(EGroup $group): void
    {
        CacheInvalidationService::invalidate('groups', cascade: true);
    }
}
