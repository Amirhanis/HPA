<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class DashboardController extends Controller
{

    public function downloadInvoice(Order $order)
{
    $pdf = PDF::loadView('pdf.invoice', ['order' => $order]);
    return $pdf->download("invoice-{$order->id}.pdf");
}

public function index()
{
    $orders = Order::with('order_items.product.brand', 'order_items.product.category')->get();
    $orders->each(function ($order) {
        $order->invoice_url = route('orders.download.invoice', $order);
    });
    return Inertia::render('User/Dashboard', ['orders' => $orders]);
}
}
