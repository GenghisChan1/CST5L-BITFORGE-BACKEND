<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DistanceController extends Controller
{
    public function calculateDistance(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $apiKey = env('ORS_API_KEY');

        try {
            $fromResp = Http::get("https://api.openrouteservice.org/geocode/search", [
                'api_key' => $apiKey,
                'text' => $from
            ]);

            if (!$fromResp->successful()) {
                Log::error('Geocode FROM failed', ['response' => $fromResp->body()]);
                return response()->json(['error' => 'Invalid origin address'], 500);
            }

            $fromCoord = $fromResp->json()['features'][0]['geometry']['coordinates'];

            $toResp = Http::get("https://api.openrouteservice.org/geocode/search", [
                'api_key' => $apiKey,
                'text' => $to
            ]);

            if (!$toResp->successful()) {
                Log::error('Geocode TO failed', ['response' => $toResp->body()]);
                return response()->json(['error' => 'Invalid destination address'], 500);
            }

            $toCoord = $toResp->json()['features'][0]['geometry']['coordinates'];

            $routeResp = Http::withHeaders([
                'Authorization' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post("https://api.openrouteservice.org/v2/directions/driving-car", [
                'coordinates' => [$fromCoord, $toCoord],
            ]);

            if (!$routeResp->successful()) {
                Log::error('Route calculation failed', ['response' => $routeResp->body()]);
                return response()->json(['error' => 'Failed to calculate route'], 500);
            }

            $distance = $routeResp->json()['routes'][0]['summary']['distance'] / 1000;

            return response()->json(['distance' => $distance]);
        } catch (\Exception $e) {
            Log::error('Distance calculation exception', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}
