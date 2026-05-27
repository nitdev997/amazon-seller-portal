<?php

namespace App\Services\Amazon;

use App\Exceptions\RdtPendingReviewException;
use App\Models\AmazonAccount;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpApiService
{
    private const ORDERS_API_PATH = '/orders/v0/orders';

    // SP-API rate limits
    // buyerInfo endpoint : 0.0167 req/s → sleep 0.7s between calls
    // orderItems endpoint: 0.5 req/s   → sleep 2s between calls
    private const SLEEP_BUYER_INFO_MS  = 700000;  // 0.7s
    private const SLEEP_ORDER_ITEMS_MS = 2000000; // 2s

    public function __construct(
        private readonly SpApiOAuthService         $oauthService,
        private readonly CustomizationService      $customizationService,
        private readonly RestrictedDataTokenService $rdtService,
    ) {}

    // ─── Sync orders ──────────────────────────────────────────────

    public function syncOrders(AmazonAccount $account, ?Carbon $createdAfter = null, ?int $limit = null): int
    {
        $createdAfter ??= now()->subDays(30);
        $synced    = 0;
        $nextToken = null;

        // Get ONE RDT token valid for all orders (saves individual token requests)
        $rdtToken = $this->getRdtToken($account);
        Log::info('Bulk RDT token obtained for order items');

        do {
            $response = $this->fetchOrderPage($account, $createdAfter, $nextToken);

            $payload = $response['payload'] ?? [];
            $orders  = $payload['Orders'] ?? [];

            foreach ($orders as $amazonOrder) {
                // Stop early if limit reached
                if ($limit !== null && $synced >= $limit) {
                    break 2;
                }
                $orderId = $amazonOrder['AmazonOrderId'];

                // 1. Fetch buyer info (PII — skipped silently if 403)
                usleep(self::SLEEP_BUYER_INFO_MS);
                try {
                    $buyerInfo = $this->fetchBuyerInfo($account, $orderId);
                    $amazonOrder['BuyerInfo'] = array_merge(
                        $amazonOrder['BuyerInfo'] ?? [],
                        $buyerInfo
                    );
                } catch (RdtPendingReviewException $e) {
                    // Buyer PII not accessible yet — skip silently
                } catch (\Exception $e) {
                    Log::warning("Could not fetch buyer info for {$orderId}: " . $e->getMessage());
                }

                // 2. Upsert order row
                $order = $this->upsertOrder($account, $amazonOrder);

                // 3. Fetch order items with RDT token — required for BuyerCustomizedInfo
                usleep(self::SLEEP_ORDER_ITEMS_MS);
                try {
                    $items = $this->fetchOrderItems($account, $orderId, $rdtToken);
                    if (!empty($items)) {
                        $this->upsertOrderItems($order, $items);
                    }
                } catch (\Exception $e) {
                    Log::warning("Could not fetch items for {$orderId}: " . $e->getMessage());
                }

                $synced++;
            }

            $nextToken = $payload['NextToken'] ?? null;

        } while ($nextToken);

        $account->update(['last_synced_at' => now()]);

        return $synced;
    }

    // ─── Fetch a page of orders ───────────────────────────────────

    private function fetchOrderPage(
        AmazonAccount $account,
        Carbon $createdAfter,
        ?string $nextToken = null
    ): array {
        $params = [
            'MarketplaceIds' => $account->marketplace_id ?? config('amazon-sp-api.default_marketplace_id'),
            'CreatedAfter'   => $createdAfter->utc()->format('Y-m-d\TH:i:s\Z'),
        ];

        if ($nextToken) {
            $params['NextToken'] = $nextToken;
        }

        $response = $this->spApiGet($account, self::ORDERS_API_PATH, $params);

        if ($response->failed()) {
            throw new \Exception('Amazon API error: ' . $response->body());
        }

        return $response->json();
    }

    // ─── Fetch buyer info (PII — may 403 until RDT is approved) ──

    private function fetchBuyerInfo(AmazonAccount $account, string $orderId): array
    {
        $response = $this->spApiGet(
            $account,
            self::ORDERS_API_PATH . "/{$orderId}/buyerInfo"
        );

        if ($response->successful()) {
            return $response->json()['payload'] ?? [];
        }

        $code = $response->json()['errors'][0]['code'] ?? '';

        // 403 / Unauthorized = RDT not approved yet, skip silently
        if ($response->status() === 403 || $code === 'Unauthorized') {
            throw new RdtPendingReviewException('BuyerInfo not accessible');
        }

        // QuotaExceeded — wait 10s and retry once
        if ($code === 'QuotaExceeded') {
            Log::info("BuyerInfo QuotaExceeded for {$orderId}, retrying in 10s");
            sleep(10);
            $retry = $this->spApiGet($account, self::ORDERS_API_PATH . "/{$orderId}/buyerInfo");
            if ($retry->successful()) {
                return $retry->json()['payload'] ?? [];
            }
            throw new RdtPendingReviewException('BuyerInfo quota exceeded');
        }

        throw new \Exception($response->body());
    }

    // ─── Fetch order items ────────────────────────────────────────

    /**
     * BuyerCustomizedInfo is returned here automatically for
     * Amazon Custom orders — no RDT required, just the regular token.
     */
    private function fetchOrderItems(AmazonAccount $account, string $orderId, ?string $rdtToken = null): array
    {
        $items     = [];
        $nextToken = null;

        do {
            $params   = $nextToken ? ['NextToken' => $nextToken] : [];
            $response = $this->spApiGetWithToken(
                $account,
                self::ORDERS_API_PATH . "/{$orderId}/orderItems",
                $rdtToken,
                $params
            );

            if ($response->failed()) {
                throw new \Exception($response->body());
            }

            $payload   = $response->json()['payload'] ?? [];
            $items     = array_merge($items, $payload['OrderItems'] ?? []);
            $nextToken = $payload['NextToken'] ?? null;

        } while ($nextToken);

        return $items;
    }

    // ─── Shared HTTP helper ───────────────────────────────────────

    private function spApiGet(AmazonAccount $account, string $path, array $params = [])
    {
        $token    = $this->oauthService->getValidAccessToken($account);
        $endpoint = $this->getEndpointForMarketplace(
            $account->marketplace_id ?? config('amazon-sp-api.default_marketplace_id')
        );

        return Http::withHeaders([
            'x-amz-access-token' => $token,
            'Content-Type'       => 'application/json',
        ])->get("{$endpoint}{$path}", $params);
    }

    // ─── Upsert order ─────────────────────────────────────────────

    private function upsertOrder(AmazonAccount $account, array $data): Order
    {
        $shippingAddress = $data['ShippingAddress'] ?? null;
        $orderTotal      = $data['OrderTotal'] ?? null;
        $buyerInfo       = $data['BuyerInfo'] ?? [];

        return Order::withoutGlobalScope('tenant')->updateOrCreate(
            ['amazon_order_id' => $data['AmazonOrderId']],
            [
                'tenant_id'                 => $account->tenant_id,
                'amazon_account_id'         => $account->id,
                'marketplace_id'            => $data['MarketplaceId'] ?? null,
                'seller_order_id'           => $data['SellerOrderId'] ?? null,
                'order_status'              => $data['OrderStatus'],
                'fulfillment_channel'       => $data['FulfillmentChannel'] ?? null,
                'sales_channel'             => $data['SalesChannel'] ?? null,
                'is_business_order'         => filter_var($data['IsBusinessOrder'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_prime'                  => filter_var($data['IsPrime'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_replacement_order'      => filter_var($data['IsReplacementOrder'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'buyer_email'               => $buyerInfo['BuyerEmail'] ?? null,
                'buyer_name'                => $buyerInfo['BuyerName'] ?? null,
                'order_total'               => $orderTotal['Amount'] ?? null,
                'currency_code'             => $orderTotal['CurrencyCode'] ?? null,
                'number_of_items_shipped'   => $data['NumberOfItemsShipped'] ?? 0,
                'number_of_items_unshipped' => $data['NumberOfItemsUnshipped'] ?? 0,
                'purchase_date'             => $data['PurchaseDate'] ?? null,
                'last_update_date'          => $data['LastUpdateDate'] ?? null,
                'earliest_ship_date'        => $data['EarliestShipDate'] ?? null,
                'latest_ship_date'          => $data['LatestShipDate'] ?? null,
                'earliest_delivery_date'    => $data['EarliestDeliveryDate'] ?? null,
                'latest_delivery_date'      => $data['LatestDeliveryDate'] ?? null,
                'shipping_address'          => $shippingAddress,
                'raw_data'                  => $data,
            ]
        );
    }

    // ─── Upsert order items ───────────────────────────────────────

    private function upsertOrderItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $itemPrice        = $item['ItemPrice'] ?? null;
            $itemTax          = $item['ItemTax'] ?? null;
            $shipping         = $item['ShippingPrice'] ?? null;
            $customizationUrl = $item['BuyerCustomizedInfo']['CustomizedURL'] ?? null;

            // Log if BuyerCustomizedInfo exists so we can debug
            if (!empty($item['BuyerCustomizedInfo'])) {
                Log::info('BuyerCustomizedInfo found', [
                    'order_item_id'    => $item['OrderItemId'] ?? null,
                    'customization_url' => $customizationUrl,
                ]);
            }

            // Parse customization ZIP if URL present
            $customizationData = null;
            if ($customizationUrl) {
                $customizationData = $this->customizationService->fetchAndParse($customizationUrl, $item['OrderItemId'] ?? null);
            }

            OrderItem::withoutGlobalScope('tenant')->updateOrCreate(
                [
                    'order_id'      => $order->id,
                    'order_item_id' => $item['OrderItemId'] ?? null,
                ],
                [
                    'tenant_id'          => $order->tenant_id,
                    'asin'               => $item['ASIN'] ?? null,
                    'seller_sku'         => $item['SellerSKU'] ?? null,
                    'title'              => $item['Title'] ?? null,
                    'quantity_ordered'   => $item['QuantityOrdered'] ?? 0,
                    'quantity_shipped'   => $item['QuantityShipped'] ?? 0,
                    'item_price'         => $itemPrice['Amount'] ?? null,
                    'item_tax'           => $itemTax['Amount'] ?? null,
                    'shipping_price'     => $shipping['Amount'] ?? null,
                    'currency_code'      => $itemPrice['CurrencyCode'] ?? null,
                    'customization_url'  => $customizationUrl,
                    'customization_data' => $customizationData,
                    'raw_data'           => $item,
                ]
            );
        }
    }

    // ─── Get RDT token for BuyerCustomizedInfo ──────────────────

    /**
     * Delegates to RestrictedDataTokenService — bulk token for all orders.
     */
    private function getRdtToken(AmazonAccount $account): ?string
    {
        return $this->rdtService->getBulkOrderItemsToken($account);
    }

    // ─── Shared HTTP helper with custom token ─────────────────────

    /**
     * Same as spApiGet but uses a provided token (e.g. RDT token)
     * instead of fetching a fresh access token.
     */
    private function spApiGetWithToken(AmazonAccount $account, string $path, ?string $token = null, array $params = [])
    {
        if (!$token) {
            // Fallback to normal access token if no RDT token provided
            return $this->spApiGet($account, $path, $params);
        }

        $endpoint = $this->getEndpointForMarketplace(
            $account->marketplace_id ?? config('amazon-sp-api.default_marketplace_id')
        );

        return Http::withHeaders([
            'x-amz-access-token' => $token,
            'Content-Type'       => 'application/json',
        ])->get("{$endpoint}{$path}", $params);
    }

    // ─── Debug helper ────────────────────────────────────────────

    /**
     * Dump the raw API response for a single order's items.
     * Use this to confirm whether BuyerCustomizedInfo is returned.
     * Remove after debugging.
     */
    public function debugOrderItems(AmazonAccount $account, string $orderId): array
    {
        $response = $this->spApiGet(
            $account,
            self::ORDERS_API_PATH . "/{$orderId}/orderItems"
        );

        return [
            'status'           => $response->status(),
            'order_id'         => $orderId,
            'raw_response'     => $response->json(),
            'has_customization' => collect($response->json()['payload']['OrderItems'] ?? [])
                ->map(fn($item) => [
                    'order_item_id'          => $item['OrderItemId'] ?? null,
                    'asin'                   => $item['ASIN'] ?? null,
                    'has_buyer_custom_info'  => isset($item['BuyerCustomizedInfo']),
                    'buyer_custom_info'      => $item['BuyerCustomizedInfo'] ?? null,
                    'all_keys'               => array_keys($item),
                ])->toArray(),
        ];
    }

    // ─── Marketplace endpoint map ─────────────────────────────────

    private function getEndpointForMarketplace(?string $marketplaceId): string
    {
        return match (true) {
            in_array($marketplaceId, ['ATVPDKIKX0DER', 'A2EUQ1WTGCTBG2', 'A1AM78C64UM0Y8'])
                => 'https://sellingpartnerapi-na.amazon.com',
            in_array($marketplaceId, ['A1F83G8C2ARO7P', 'A1PA6795UKMFR9', 'APJ6JRA9NG5V4', 'A13V1IB3VIYZZH'])
                => 'https://sellingpartnerapi-eu.amazon.com',
            in_array($marketplaceId, ['A1VC38T7YXB528', 'AAHKV2X7AFYLW'])
                => 'https://sellingpartnerapi-fe.amazon.com',
            default
                => 'https://sellingpartnerapi-na.amazon.com',
        };
    }
}