<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

trait IPHelperTrait
{
    use LogExceptionTrait;

    public function getIP(Request $request): string
    {
        // Check for CloudFront forwarded ip
        if (!empty($request->header('x-vapor-source-ip'))) {
            return $request->header('x-vapor-source-ip');
        }

        // Check for CloudFlare forwarded ip
        if (!empty($request->header('cf-connecting-ip'))) {
            return $request->header('cf-connecting-ip');
        }

        // Default
        return $request->ip();
    }

    public function getIPCountryCode(Request $request): string|null
    {
        // Parse ip address from request
        $ip = $this->getIP($request);

        // Validate ip and fetch ip country code and cache for 10 minutes
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return Cache::remember(sprintf('ip-country:%s', md5($ip)), 600, function () use ($ip) {
                try {
                    $response = Http::timeout(10)
                        ->connectTimeout(10)
                        ->get(sprintf('https://api.country.is/%s', $ip));
                    if ($response->successful()) {
                        return $response->json('country');
                    }
                } catch (Throwable $exception) {
                    $this->logException('Failed to get country code from ip address', $exception, [
                        'hashed_ip' => md5($ip),
                    ]);
                }
                return null;
            });
        }

        // Default
        return null;
    }
}
