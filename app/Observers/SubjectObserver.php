<?php

namespace App\Observers;

use App\Models\ESubject;
use App\Services\CacheInvalidationService;

class SubjectObserver
{
    public function created(ESubject $subject): void
    {
        CacheInvalidationService::invalidate('subjects', cascade: true);
    }

    public function updated(ESubject $subject): void
    {
        CacheInvalidationService::invalidate('subjects', cascade: true);
        CacheInvalidationService::invalidateKey("subjects:id:{$subject->id}");
    }

    public function deleted(ESubject $subject): void
    {
        CacheInvalidationService::invalidate('subjects', cascade: true);
    }
}
