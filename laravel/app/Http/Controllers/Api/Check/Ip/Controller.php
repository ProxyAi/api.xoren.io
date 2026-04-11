<?php

namespace App\Http\Controllers\Api\Check\Ip;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class Controller extends \App\Http\Controllers\Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $clientIp = $request->ip();
        $ipv4 = null;
        $ipv6 = null;

        if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipv4 = $clientIp;
        } elseif (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipv6 = $clientIp;
        }

        return response()->json([
            'ipv4' => $ipv4,
            'ipv6' => $ipv6,
        ]);
    }
}
