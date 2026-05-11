<?php

namespace App\Services\Amazon;

use App\Models\AmazonAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generates Restricted Data Tokens (RDT) for accessing PII-adjacent
 * SP-API data such as BuyerCustomizedInfo in order items.
 *
 * Docs: https://developer-docs.amazon.com/sp-api/docs/tokens-api-use-case-guide
 */
class RestrictedDataTokenService
{
    private const TOKENS_API_PATH = '/tokens/2021-03-01/restrictedDataToken';

    public function __construct(
        private readonly SpApiOAuthService $oauthService
    ) {}

    /**
     * Request an RDT for getOrderItems with buyerCustomizedInfo access.
     *
     * Returns the RDT string, or null if the request fails.
     */
    public function getOrderItemsToken(AmazonAccount $account, string $orderId): ?string
    {
        try {
            $accessToken = $this->oauthService->getValidAccessToken($account);
            $endpoint    = $this->getEndpoint($account->marketplace_id);

            $response = Http::withHeaders([
                'x-amz-access-token' => $accessToken,
                'Content-Type'       => 'application/json',
            ])->post("{$endpoint}" . self::TOKENS_API_PATH, [
                'restrictedResources' => [
                    [
                        // The specific order items path for this order
                        'method'             => 'GET',
                        'path'               => "/orders/v0/orders/{$orderId}/orderItems",
                        'dataElements'       => ['buyerInfo', 'shippingAddress'],
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::warning('RDT request failed', [
                    'order_id' => $orderId,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);
                return null;
            }

            return $response->json()['restrictedDataToken'] ?? null;

        } catch (\Exception $e) {
            Log::warning('RDT exception: ' . $e->getMessage());
            return null;
        }
    }

    private function getEndpoint(?string $marketplaceId): string
    {
        return match (true) {
            in_array($marketplaceId, ['ATVPDKIKX0DER', 'A2EUQ1WTGCTBG2', 'A1AM78C64UM0Y8'])
                => 'https://sellingpartnerapi-na.amazon.com',
            in_array($marketplaceId, ['A1F83G8C2ARO7P', 'A1PA6795UKMFR9', 'APJ6JRA9NG5V4', 'A13V1IB3VIYZZH'])
                => 'https://sellingpartnerapi-eu.amazon.com',
            in_array($marketplaceId, ['A1VC38T7YXB528', 'AAHKV2X7AFYLW'])
                => 'https://sellingpartnerapi-fe.amazon.com',
            default => 'https://sellingpartnerapi-na.amazon.com',
        };
    }
}
