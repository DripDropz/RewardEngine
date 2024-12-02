<?php

namespace App\Traits;

use App\Models\Project;
use Illuminate\Http\Request;

trait GEOBlockTrait
{
    use IPTrait;

    private function isGEOBlocked(Project $project, Request $request): bool
    {
        // Check if geo-blocked country codes is defined
        if (!empty($project->geo_blocked_countries)) {

            // Parse geo-blocked country codes
            $geoBlockedCountryCodes = array_values(array_filter(explode(',', $project->geo_blocked_countries)));
            $geoBlockedCountryCodes = array_map('trim', $geoBlockedCountryCodes);

            // Get request originating country code
            $requestCountryCode = $this->getIPCountryCode($request);

            // Determine if request country code is geo-blocked
            return in_array($requestCountryCode, $geoBlockedCountryCodes);
        }

        // Default
        return false;
    }
}
