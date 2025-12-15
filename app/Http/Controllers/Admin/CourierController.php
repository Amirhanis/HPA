<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TrackingMoreService;
use Illuminate\Http\Request;

class CourierController extends Controller
{
    public function index(Request $request, TrackingMoreService $trackingMore)
    {
        $query = (string) $request->query('q', '');

        return response()->json([
            'ok' => true,
            'data' => $trackingMore->listCouriers($query),
        ]);
    }
}
