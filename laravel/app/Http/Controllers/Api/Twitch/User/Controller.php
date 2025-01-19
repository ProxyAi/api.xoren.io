<?php

namespace App\Http\Controllers\Api\Twitch\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

class Controller extends \App\Http\Controllers\Controller
{
    public function __invoke(Request $request, string $username): JsonResponse
    {
        try {
            $tokenResponse = Http::asForm()->post('https://id.twitch.tv/oauth2/token', [
                'client_id' => config('services.twitch.client_id'),
                'client_secret' => config('services.twitch.client_secret'),
                'grant_type' => 'client_credentials',
            ]);

            if (!$tokenResponse->successful()) {
                return response()->json([
                    'error' => 'Failed to authenticate with Twitch',
                    'details' => $tokenResponse->json(),
                ], 500);
            }

            $accessToken = $tokenResponse->json()['access_token'];
            $headers = [
                'Client-ID' => config('services.twitch.client_id'),
                'Authorization' => 'Bearer ' . $accessToken,
            ];

            // Get user data first
            $userResponse = Http::withHeaders($headers)
                ->get("https://api.twitch.tv/helix/users", [
                    'login' => $username,
                ]);

            if (!$userResponse->successful()) {
                return response()->json([
                    'error' => 'Failed to fetch user data',
                    'details' => $userResponse->json(),
                ], 500);
            }

            $userData = $userResponse->json();
            
            if (empty($userData['data'])) {
                return response()->json([
                    'error' => 'User not found',
                ], 404);
            }

            $user = $userData['data'][0];
            
            // Get stream data
            $streamResponse = Http::withHeaders($headers)
                ->get("https://api.twitch.tv/helix/streams", [
                    'user_id' => $user['id'],
                ]);

            if (!$streamResponse->successful()) {
                return response()->json([
                    'error' => 'Failed to fetch stream data',
                    'details' => $streamResponse->json(),
                ], 500);
            }

            $streamData = $streamResponse->json();

            // Get channel information
            $channelResponse = Http::withHeaders($headers)
                ->get("https://api.twitch.tv/helix/channels", [
                    'broadcaster_id' => $user['id'],
                ]);

            if (!$channelResponse->successful()) {
                return response()->json([
                    'error' => 'Failed to fetch channel data',
                    'details' => $channelResponse->json(),
                ], 500);
            }

            $channelData = $channelResponse->json();

            return response()->json([
                'user' => [
                    'id' => $user['id'],
                    'login' => $user['login'],
                    'display_name' => $user['display_name'],
                    'type' => $user['type'],
                    'broadcaster_type' => $user['broadcaster_type'],
                    'description' => $user['description'],
                    'profile_image_url' => $user['profile_image_url'],
                    'offline_image_url' => $user['offline_image_url'],
                    'created_at' => $user['created_at'],
                ],
                'channel' => $channelData['data'][0] ?? null,
                'stream' => [
                    'is_live' => !empty($streamData['data']),
                    'data' => $streamData['data'][0] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}