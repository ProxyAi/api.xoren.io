<?php

namespace App\Http\Controllers\Api\Twitch\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;

class Controller extends \App\Http\Controllers\Controller
{
    private const TWITCH_GQL_URL = 'https://gql.twitch.tv/gql';
    private const TWITCH_CLIENT_ID = 'kimne78kx3ncx6brgo4mv6wki5h1ko';

    public function __invoke(Request $request, string $username): JsonResponse
    {
        try {
            // Clean the username - remove trailing slashes and spaces
            $username = trim($username, "/ \t\n\r\0\x0B");
            
            // Query for full user data following the documented structure
            $query = <<<GQL
            query {
                user(login: "{$username}") {
                    id
                    login
                    displayName
                    description
                    createdAt
                    roles {
                        isPartner
                        isAffiliate
                    }
                    profileImageURL(width: 300)
                    offlineImageURL
                    lastBroadcast {
                        title
                        game {
                            name
                        }
                    }
                    stream {
                        id
                        title
                        type
                        viewersCount
                        createdAt
                        game {
                            name
                        }
                    }
                }
            }
            GQL;

            // Make GraphQL request
            $response = Http::withHeaders([
                'Client-ID' => self::TWITCH_CLIENT_ID,
                'Content-Type' => 'application/json',
            ])->post(self::TWITCH_GQL_URL, [
                'query' => $query,
                'variables' => (object)[]
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Failed to fetch Twitch data',
                    'details' => $response->json()
                ], 500);
            }

            $data = $response->json();

            // Validate response structure
            if (!isset($data['data'])) {
                return response()->json([
                    'error' => 'Invalid response format',
                    'details' => $data
                ], 500);
            }

            // Check if user exists
            if (empty($data['data']['user'])) {
                return response()->json([
                    'error' => 'User not found'
                ], 404);
            }

            $user = $data['data']['user'];

            // Fall back to basic stream status if we hit any issues
            try {
                return response()->json([
                    'user' => [
                        'id' => $user['id'],
                        'login' => $user['login'],
                        'display_name' => $user['displayName'],
                        'type' => $user['roles']['isPartner'] ? 'partner' : 
                                ($user['roles']['isAffiliate'] ? 'affiliate' : ''),
                        'description' => $user['description'],
                        'profile_image_url' => $user['profileImageURL'],
                        'offline_image_url' => $user['offlineImageURL'],
                        'created_at' => $user['createdAt']
                    ],
                    'channel' => [
                        'broadcaster_language' => 'en',
                        'game_name' => $user['lastBroadcast']['game']['name'] ?? null,
                        'title' => $user['lastBroadcast']['title'] ?? null
                    ],
                    'stream' => [
                        'is_live' => !empty($user['stream']),
                        'data' => $user['stream'] ? [
                            'id' => $user['stream']['id'],
                            'game_name' => $user['stream']['game']['name'],
                            'title' => $user['stream']['title'],
                            'viewer_count' => $user['stream']['viewersCount'],
                            'started_at' => $user['stream']['createdAt'],
                            'thumbnail_url' => sprintf(
                                'https://static-cdn.jtvnw.net/previews-ttv/live_user_%s-{width}x{height}.jpg',
                                strtolower($user['login'])
                            )
                        ] : null
                    ]
                ]);
            } catch (\Exception $e) {
                // If we hit any issues parsing the full response, just return stream status
                return response()->json([
                    'is_live' => !empty($user['stream']),
                    'stream_id' => $user['stream']['id'] ?? null
                ]);
            }

        } catch (\Exception $e) {
            logger()->error('Twitch GraphQL API Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'An error occurred',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}