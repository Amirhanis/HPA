<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TrackingMoreWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Optional protection: set TRACKINGMORE_WEBHOOK_SECRET
        $secret = (string) config('services.trackingmore.webhook_secret');
        if ($secret !== '') {
            $provided = (string) ($request->header('X-Webhook-Secret') ?? $request->query('secret', ''));
            abort_unless(hash_equals($secret, $provided), 401);
        }

        $payload = $request->all();

        $trackingNumber = data_get($payload, 'data.tracking_number') ?? data_get($payload, 'tracking_number');
        $courierCode = data_get($payload, 'data.courier_code') ?? data_get($payload, 'courier_code');

        if (!$trackingNumber || !$courierCode) {
            return response()->json(['ok' => true]);
        }

        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->where('courier_code', $courierCode)
            ->first();

        if (!$shipment) {
            return response()->json(['ok' => true]);
        }

        $shipment->delivery_status = data_get($payload, 'data.delivery_status')
            ?? data_get($payload, 'delivery_status')
            ?? $shipment->delivery_status;

        $shipment->latest_event = data_get($payload, 'data.latest_event')
            ?? data_get($payload, 'latest_event')
            ?? $shipment->latest_event;

        $checkpointTime = data_get($payload, 'data.latest_checkpoint_time') ?? data_get($payload, 'latest_checkpoint_time');
        if ($checkpointTime) {
            try {
                $shipment->latest_checkpoint_time = Carbon::parse($checkpointTime);
            } catch (\Throwable $e) {
                // ignore parse issues
            }
        }

        $shipment->raw = $payload;
        $shipment->save();

        return response()->json(['ok' => true]);
    }
}
