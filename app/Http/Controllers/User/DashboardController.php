<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\TrackingMoreService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class DashboardController extends Controller
{

    public function downloadInvoice(Request $request, Order $order)
    {
        abort_unless($request->user() && (int) $order->created_by === (int) $request->user()->id, 403);

        $pdf = PDF::loadView('pdf.invoice', ['order' => $order]);
        return $pdf->download("invoice-{$order->id}.pdf");
    }

    public function index(Request $request)
    {
        $orders = Order::with('shipment', 'order_items.product.brand', 'order_items.product.category')
            ->where('created_by', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        $orders->each(function ($order) {
            $order->invoice_url = route('orders.download.invoice', $order);
        });

        return Inertia::render('User/Dashboard', ['orders' => $orders]);
    }

    public function tracking(Request $request, Order $order, TrackingMoreService $trackingMore)
    {
        abort_unless($request->user() && (int) $order->created_by === (int) $request->user()->id, 403);

        $order->load('shipment');

        if (!$order->shipment) {
            return response()->json([
                'ok' => false,
                'message' => 'No shipment tracking added yet.',
            ], 404);
        }

        $remote = null;
        $remoteError = null;

        try {
            $remote = $trackingMore->getTracking(
                $order->shipment->tracking_number,
                $order->shipment->courier_code
            );

            $order->shipment->delivery_status = data_get($remote, 'data.delivery_status') ?? $order->shipment->delivery_status;
            $order->shipment->latest_event = data_get($remote, 'data.latest_event') ?? $order->shipment->latest_event;

            $checkpoint = data_get($remote, 'data.latest_checkpoint_time');
            if ($checkpoint) {
                try {
                    $order->shipment->latest_checkpoint_time = Carbon::parse($checkpoint);
                } catch (\Throwable $e) {
                    // ignore parse issues
                }
            }

            $order->shipment->raw = $remote;
            $order->shipment->save();
        } catch (\Throwable $e) {
            $remoteError = $e->getMessage();
        }

        return response()->json([
            'ok' => true,
            'delivery_status' => $order->shipment->delivery_status,
            'latest_event' => $order->shipment->latest_event,
            'latest_checkpoint_time' => optional($order->shipment->latest_checkpoint_time)->toIso8601String(),
            'stale' => $remote === null,
            'error_message' => $remoteError,
        ]);
    }
}
