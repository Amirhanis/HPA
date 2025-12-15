<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        $orders = Order::with('shipment')
            ->orderByDesc('id')
            ->paginate(15);

        return Inertia::render('Admin/Dashboard', [
            'orders' => $orders,
        ]);
    }

    function salesPerformance()
    {
        $salesData = Order::selectRaw('DATE(created_at) as date, SUM(total_price) as total_sales')
            ->where('status', 'paid')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($salesData);
    }

    public function salesReport()
{
    // Get sales data by date
    $salesData = Order::selectRaw('DATE(created_at) as date, SUM(total_price) as revenue')
        ->where('status', 'paid')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    // Calculate total expenses (price * quantity) for all products
    $expensesData = Product::selectRaw('DATE(created_at) as date, SUM(price * quantity) as revenue')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    // Get total expenses sum
    $totalExpenses = Product::sum(DB::raw('price * quantity'));

    return Inertia::render('Admin/SalesReport', [
        'salesData' => $salesData,
        'expensesData' => $expensesData,
        'totalExpenses' => $totalExpenses
    ]);
}
}
