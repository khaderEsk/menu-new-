<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class OsrmService
{
    protected $baseUrl;

    public function __construct()
    {
        // Get the OSRM server URL from your .env file
        $this->baseUrl = config('services.osrm.base_url');
    }

    /**
     * Get route data from OSRM.
     *
     * @return array|null
     */
    public function getRoute(float $startLat, float $startLon, float $endLat, float $endLon): ?array
    {
        $url = "{$this->baseUrl}/route/v1/bicycle/{$startLon},{$startLat};{$endLon},{$endLat}";

        try {
            $response = Http::timeout(15)->get($url);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            if ($data['code'] !== 'Ok' || empty($data['routes'])) {
                // OSRM could not find a route
                return null;
            }

            return $data['routes'][0];

        } catch (RequestException $e) {
            report($e);
            return null;
        }
    }
}