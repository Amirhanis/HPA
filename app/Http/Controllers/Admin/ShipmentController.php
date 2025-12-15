<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\TrackingMoreService;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function store(Request $request, Order $order, TrackingMoreService $trackingMore)
    {
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'max:255'],
            'courier_code' => ['required', 'string', 'max:100'],
        ]);

        $shipment = Shipment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'tracking_number' => $data['tracking_number'],
                'courier_code' => $data['courier_code'],
            ]
        );

        // Register/create tracking in TrackingMore
        $created = $trackingMore->createTracking($shipment->tracking_number, $shipment->courier_code);

        $shipment->trackingmore_id = data_get($created, 'data.id') ?? $shipment->trackingmore_id;
        $shipment->delivery_status = data_get($created, 'data.delivery_status') ?? $shipment->delivery_status;
        $shipment->latest_event = data_get($created, 'data.latest_event') ?? $shipment->latest_event;
        $shipment->raw = $created;
        $shipment->save();

        return back()->with('success', 'Shipment tracking saved.');
    }
}
