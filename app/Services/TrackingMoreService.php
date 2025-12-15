<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class TrackingMoreService
{
    private string $baseUrl = 'https://api.trackingmore.com/v4';

    private function client()
    {
        return Http::baseUrl($this->baseUrl)->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Tracking-Api-Key' => (string) config('services.trackingmore.key'),
        ]);
    }

    public function createTracking(string $trackingNumber, string $courierCode): array
    {
        $trackingNumber = trim($trackingNumber);
        $courierCode = trim($courierCode);

        if ($trackingNumber === '' || $courierCode === '') {
            throw new InvalidArgumentException('Tracking number and courier code are required.');
        }

        $res = $this->client()->post('/trackings/create', [
            'tracking_number' => $trackingNumber,
            'courier_code' => $courierCode,
        ]);

        $res->throw();

        return (array) $res->json();
    }

    public function getTracking(string $trackingNumber, string $courierCode): array
    {
        $trackingNumber = trim($trackingNumber);
        $courierCode = trim($courierCode);

        if ($trackingNumber === '' || $courierCode === '') {
            throw new InvalidArgumentException('Tracking number and courier code are required.');
        }

        $attempts = [
            // Common v4 pattern: POST JSON
            fn () => $this->client()->post('/trackings/get', [
                'tracking_number' => $trackingNumber,
                'courier_code' => $courierCode,
            ]),

            // Some APIs use carrier_code
            fn () => $this->client()->post('/trackings/get', [
                'tracking_number' => $trackingNumber,
                'carrier_code' => $courierCode,
            ]),

            // Fallback: GET query
            fn () => $this->client()->get('/trackings/get', [
                'tracking_number' => $trackingNumber,
                'courier_code' => $courierCode,
            ]),
            fn () => $this->client()->get('/trackings/get', [
                'tracking_number' => $trackingNumber,
                'carrier_code' => $courierCode,
            ]),

            // Fallback: path-style endpoints seen in earlier versions
            fn () => $this->client()->get("/trackings/get/{$courierCode}/{$trackingNumber}"),
            fn () => $this->client()->get("/trackings/{$courierCode}/{$trackingNumber}"),
        ];

        $lastResponse = null;

        foreach ($attempts as $attempt) {
            $res = $attempt();
            $lastResponse = $res;

            $json = (array) $res->json();
            $metaCode = data_get($json, 'meta.code');

            if ($res->successful() && ($metaCode === 200 || data_get($json, 'data') !== null)) {
                return $json;
            }
        }

        $lastResponse?->throw();

        return [];
    }

    /**
     * Returns a list of couriers from TrackingMore.
     * Output format: [['code' => 'dhl', 'name' => 'DHL'], ...]
     */
    public function listCouriers(string $query = ''): array
    {
        $cacheKey = 'trackingmore:couriers:v1';

        $couriers = Cache::remember($cacheKey, now()->addDay(), function () {
            $attempts = [
                fn () => $this->client()->get('/couriers/all'),
                fn () => $this->client()->get('/couriers'),
                fn () => $this->client()->get('/couriers/get'),
                fn () => $this->client()->get('/carriers/all'),
                fn () => $this->client()->get('/carriers'),
            ];

            foreach ($attempts as $attempt) {
                try {
                    $res = $attempt();
                    $json = (array) $res->json();

                    // Common shapes: { data: [...] } or { data: { items: [...] } }
                    $items = data_get($json, 'data');
                    if (is_array($items) && array_is_list($items)) {
                        return $this->normalizeCouriers($items);
                    }

                    $items = data_get($json, 'data.items');
                    if (is_array($items) && array_is_list($items)) {
                        return $this->normalizeCouriers($items);
                    }
                } catch (\Throwable $e) {
                    // try next endpoint
                }
            }

            return [];
        });

        $query = trim($query);
        if ($query === '') {
            return $couriers;
        }

        $q = mb_strtolower($query);

        return array_values(array_filter($couriers, function (array $c) use ($q) {
            return str_contains(mb_strtolower((string) ($c['code'] ?? '')), $q)
                || str_contains(mb_strtolower((string) ($c['name'] ?? '')), $q);
        }));
    }

    private function normalizeCouriers(array $items): array
    {
        $out = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $code = (string) (data_get($item, 'courier_code')
                ?? data_get($item, 'carrier_code')
                ?? data_get($item, 'code')
                ?? '');

            $name = (string) (data_get($item, 'courier_name')
                ?? data_get($item, 'carrier_name')
                ?? data_get($item, 'name')
                ?? $code);

            $code = trim($code);
            $name = trim($name);

            if ($code === '') {
                continue;
            }

            $out[] = [
                'code' => $code,
                'name' => $name,
            ];
        }

        // De-duplicate by code
        $unique = [];
        foreach ($out as $c) {
            $unique[$c['code']] = $c;
        }

        return array_values($unique);
    }
}
