<?php

namespace App\Observers;

use App\Models\EDepartment;
use App\Services\CacheInvalidationService;

/**
 * Department Observer
 *
 * Automatically invalidates cache when department is created, updated, or deleted
 */
class DepartmentObserver
{
    /**
     * Handle the Department "created" event.
     */
    public function created(EDepartment $department): void
    {
        // Invalidate all department-related caches
        CacheInvalidationService::invalidate('departments', cascade: true);
    }

    /**
     * Handle the Department "updated" event.
     */
    public function updated(EDepartment $department): void
    {
        // Invalidate all department-related caches
        CacheInvalidationService::invalidate('departments', cascade: true);

        // Also invalidate specific department cache
        CacheInvalidationService::invalidateKey("departments:id:{$department->id}");
    }

    /**
     * Handle the Department "deleted" event.
     */
    public function deleted(EDepartment $department): void
    {
        // Invalidate all department-related caches
        CacheInvalidationService::invalidate('departments', cascade: true);
    }

    /**
     * Handle the Department "restored" event.
     */
    public function restored(EDepartment $department): void
    {
        CacheInvalidationService::invalidate('departments', cascade: true);
    }

    /**
     * Handle the Department "force deleted" event.
     */
    public function forceDeleted(EDepartment $department): void
    {
        CacheInvalidationService::invalidate('departments', cascade: true);
    }
}
