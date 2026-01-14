<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiRecommendationService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected $aiService;

    public function __construct(AiRecommendationService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function recommendations(Request $request)
    {
        $user = $request->user();
        $userId = $user ? $user->id : null;
        $cartItems = $request->input('cart_items', []); // Pass cart items from frontend

        $recommendations = $this->aiService->getRecommendations($userId, $cartItems);

        // Enhance recommendations with actual product data from DB if needed
        // For now, returning IDs and reasons

        // Fetch product details
        $productIds = array_column($recommendations, 'id');
        $products = \App\Models\Product::whereIn('id', $productIds)->get();

        $enhanced = [];
        foreach ($recommendations as $rec) {
            $product = $products->where('id', $rec['id'])->first();
            if ($product) {
                $rec['product'] = $product;
                $enhanced[] = $rec;
            }
        }

        return response()->json(['data' => $enhanced]);
    }

    public function chat(Request $request)
    {
        $message = $request->input('message');
        $cartItems = $request->input('cart_items', []);

        $response = $this->aiService->chat($message, $cartItems);

        return response()->json(['response' => $response]);
    }
}
