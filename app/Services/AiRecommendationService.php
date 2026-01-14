<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiRecommendationService
{
    protected $baseUrl;

    public function __construct()
    {
        // Python service URL
        $this->baseUrl = env('AI_SERVICE_URL', 'http://127.0.0.1:8001');
    }

    public function getRecommendations($userId, $cartItems = [])
    {
        try {
            $response = Http::post("{$this->baseUrl}/recommendations", [
                'user_id' => $userId,
                'cart_items' => $cartItems,
            ]);

            if ($response->successful()) {
                return $response->json()['recommendations'] ?? [];
            }
        } catch (\Exception $e) {
            // Log error
        }

        return [];
    }

    public function chat($message, $cartItems = [])
    {
        try {
            $response = Http::post("{$this->baseUrl}/chat", [
                'message' => $message,
                'cart_items' => $cartItems,
            ]);

            if ($response->successful()) {
                return $response->json()['response'] ?? 'I apologize, I am currently unavailable.';
            }
        } catch (\Exception $e) {
            // Log error
        }

        return 'System: Unable to connect to AI advisor.';
    }
}
