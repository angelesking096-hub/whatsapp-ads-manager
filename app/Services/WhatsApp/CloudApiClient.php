<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudApiClient
{
    public function sendTemplate(string $phoneNumberId, string $accessToken, string $to, string $templateName, string $language = 'fr'): array
    {
        $version = config('services.whatsapp.graph_version', 'v23.0');
        $response = Http::withToken($accessToken)->acceptJson()->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp', 'to' => $to, 'type' => 'template',
            'template' => ['name' => $templateName, 'language' => ['code' => $language]],
        ]);
        if ($response->failed()) throw new RuntimeException('WhatsApp API error: '.$response->status().' '.$response->body());
        return $response->json();
    }
}
