<?php
namespace App\Services\Admin;

class HemisService
{
    public function syncData(string $entity): array
    {
        logger()->info("HEMIS sync requested", ['entity' => $entity]);
        // HEMIS API integration uchun placeholder
        return ['status' => 'success', 'message' => 'HEMIS sync initiated', 'entity' => $entity];
    }
    
    public function getStatus(): array
    {
        return ['hemis_connected' => true, 'last_sync' => now()->subHours(2)];
    }
}
