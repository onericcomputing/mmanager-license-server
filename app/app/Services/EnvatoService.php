<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnvatoService
{
    protected string $token;
    protected ?string $itemId;

    public function __construct()
    {
        $this->token = config('services.envato.personal_token', '');
        $this->itemId = config('services.envato.item_id');
    }

    public function verify(string $purchaseCode): array
    {
        $cacheKey = 'envato_' . md5($purchaseCode);

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        if (empty($this->token)) {
            return ['valid' => false, 'error' => 'no_token', 'message' => 'Token Envato non configuré'];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'User-Agent' => 'MManager License Server',
                ])
                ->get('https://api.envato.com/v3/market/author/sale', [
                    'code' => $purchaseCode,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($this->itemId && ($data['item']['id'] ?? null) != $this->itemId) {
                    return ['valid' => false, 'error' => 'wrong_item', 'message' => 'Code invalide pour ce produit'];
                }

                $result = [
                    'valid' => true,
                    'buyer' => $data['buyer'] ?? null,
                    'license' => $data['license'] ?? 'Regular License',
                    'sold_at' => $data['sold_at'] ?? null,
                    'supported_until' => $data['supported_until'] ?? null,
                    'item_id' => $data['item']['id'] ?? null,
                    'raw' => $data,
                ];

                Cache::put($cacheKey, $result, 3600);
                return $result;
            }

            if ($response->status() === 404) {
                return ['valid' => false, 'error' => 'not_found', 'message' => 'Code d\'achat introuvable'];
            }

            return ['valid' => false, 'error' => 'api_error', 'message' => 'Erreur API Envato'];

        } catch (\Exception $e) {
            Log::error('Envato API: ' . $e->getMessage());
            return ['valid' => false, 'error' => 'connection', 'message' => 'Connexion impossible'];
        }
    }

    public function clearCache(string $purchaseCode): void
    {
        Cache::forget('envato_' . md5($purchaseCode));
    }
}
