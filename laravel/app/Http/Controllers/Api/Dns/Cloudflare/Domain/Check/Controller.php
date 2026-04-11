<?php

namespace App\Http\Controllers\Api\Dns\Cloudflare\Domain\Check;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class Controller extends \App\Http\Controllers\Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $domain
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, string $domain): JsonResponse
    {
        $cloudflareApiEmail = env('CLOUDFLARE_API_EMAIL');
        $cloudflareApiKey = env('CLOUDFLARE_API_KEY');
        $cloudflareZoneName = env('CLOUDFLARE_ZONE_NAME');

        if (!$cloudflareApiEmail || !$cloudflareApiKey || !$cloudflareZoneName) {
            return response()->json(['error' => 'Cloudflare API credentials or zone name missing in environment configuration.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $clientIpV4 = filter_var($request->ip(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $request->ip() : null;
        $clientIpV6 = filter_var($request->ip(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $request->ip() : null;

        $results = [];

        if ($clientIpV4) {
            $currentDnsIpV4 = trim(shell_exec("dig +short A {$domain} @1.1.1.1"));

            if (empty($currentDnsIpV4)) {
                $results['ipv4_update'] = ['status' => 'info', 'message' => "No A record found for {$domain}; no update attempted."];
            } elseif ($clientIpV4 === $currentDnsIpV4) {
                $results['ipv4_update'] = ['status' => 'success', 'message' => "A record for {$domain} is already set to {$clientIpV4}; no changes needed."];
            } else {
                $updateResult = $this->updateCloudflareRecord(
                    $cloudflareApiEmail,
                    $cloudflareApiKey,
                    $cloudflareZoneName,
                    $domain,
                    'A',
                    $clientIpV4
                );
                $results['ipv4_update'] = $updateResult;
            }
        } else {
            $results['ipv4_update'] = ['status' => 'skipped', 'message' => 'Client IPv4 not detected.'];
        }

        if ($clientIpV6) {
            $currentDnsIpV6 = trim(shell_exec("dig +short AAAA {$domain} @1.1.1.1"));

            if (empty($currentDnsIpV6)) {
                $results['ipv6_update'] = ['status' => 'info', 'message' => "No AAAA record found for {$domain}; no update attempted."];
            } elseif ($clientIpV6 === $currentDnsIpV6) {
                $results['ipv6_update'] = ['status' => 'success', 'message' => "AAAA record for {$domain} is already set to {$clientIpV6}; no changes needed."];
            } else {
                $updateResult = $this->updateCloudflareRecord(
                    $cloudflareApiEmail,
                    $cloudflareApiKey,
                    $cloudflareZoneName,
                    $domain,
                    'AAAA',
                    $clientIpV6
                );
                $results['ipv6_update'] = $updateResult;
            }
        } else {
            $results['ipv6_update'] = ['status' => 'skipped', 'message' => 'Client IPv6 not detected.'];
        }

        if (!$clientIpV4 && !$clientIpV6) {
            return response()->json(['error' => 'Could not determine client\'s IP address (IPv4 or IPv6) or perform checks.'], Response::HTTP_BAD_REQUEST);
        }

        return response()->json($results);
    }

    protected function updateCloudflareRecord(
        string $email,
        string $apiKey,
        string $zoneName,
        string $dnsRecordName,
        string $recordType,
        string $ipAddress
    ): array {
        $response = Http::withHeaders([
            'X-Auth-Email' => $email,
            'X-Auth-Key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->get("https://api.cloudflare.com/client/v4/zones", [
            'name' => $zoneName,
            'status' => 'active',
        ]);

        if ($response->failed()) {
            return ['status' => 'error', 'message' => 'Failed to get Cloudflare zone ID.', 'details' => $response->json()];
        }

        $zoneId = null;
        foreach ($response->json('result') ?? [] as $zone) {
            if ($zone['name'] === $zoneName) {
                $zoneId = $zone['id'];
                break;
            }
        }

        if (is_null($zoneId)) {
            return ['status' => 'error', 'message' => "Could not get zone ID for {$zoneName}."];
        }

        $response = Http::withHeaders([
            'X-Auth-Email' => $email,
            'X-Auth-Key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->get("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records", [
            'type' => $recordType,
            'name' => $dnsRecordName,
        ]);

        if ($response->failed()) {
            return ['status' => 'error', 'message' => 'Failed to get Cloudflare DNS record ID.', 'details' => $response->json()];
        }

        $dnsRecordId = null;
        foreach ($response->json('result') ?? [] as $record) {
            if ($record['name'] === $dnsRecordName && $record['type'] === $recordType) {
                $dnsRecordId = $record['id'];
                break;
            }
        }

        if (is_null($dnsRecordId)) {
            return ['status' => 'error', 'message' => "Could not find {$recordType} record for {$dnsRecordName}."];
        }

        $response = Http::withHeaders([
            'X-Auth-Email' => $email,
            'X-Auth-Key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->put("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records/{$dnsRecordId}", [
            'type' => $recordType,
            'name' => $dnsRecordName,
            'content' => $ipAddress,
            'ttl' => 1,
            'proxied' => false,
        ]);

        if ($response->successful() && $response->json('success') === true) {
            return ['status' => 'success', 'message' => "Successfully updated {$recordType} record for {$dnsRecordName} to {$ipAddress}."];
        } else {
            return ['status' => 'error', 'message' => "Failed to update {$recordType} record for {$dnsRecordName}.", 'details' => $response->json()];
        }
    }
}
