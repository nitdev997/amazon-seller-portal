<?php

namespace App\Services\Amazon;

use App\Models\AmazonAccount;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpApiService
{
    private const ORDERS_API_PATH = '/orders/v0/orders';

    public function __construct(
        private readonly SpApiOAuthService   $oauthService,
        private readonly CustomizationService $customizationService,
    ) {}

    // ─── Sync orders ──────────────────────────────────────────────

    public function syncOrders(AmazonAccount $account, ?Carbon $createdAfter = null): int
    {
        $createdAfter ??= now()->subDays(15);
        $synced    = 0;
        $nextToken = null;

        do {
            $response = $this->fetchOrderPage(
                account: $account,
                createdAfter: $createdAfter,
                nextToken: $nextToken,
            );

            $payload = $response['payload'] ?? [];
            $orders  = $payload['Orders'] ?? [];

            foreach ($orders as $amazonOrder) {
                $orderId = $amazonOrder['AmazonOrderId'];

                // 1. Fetch buyer info (separate endpoint)
                try {
                    $buyerInfo = $this->fetchBuyerInfo($account, $orderId);
                    $amazonOrder['BuyerInfo'] = array_merge(
                        $amazonOrder['BuyerInfo'] ?? [],
                        $buyerInfo
                    );
                } catch (\Exception $e) {
                    Log::warning("Could not fetch buyer info for {$orderId}: " . $e->getMessage());
                }

                // 2. Upsert the order row
                $order = $this->upsertOrder($account, $amazonOrder);

                // 3. Fetch & upsert order items (separate endpoint)
                try {
                    $items = $this->fetchOrderItems($account, $orderId);
                    if (!empty($items)) {
                        $this->upsertOrderItems($order, $items);
                    }
                } catch (\Exception $e) {
                    Log::warning("Could not fetch items for {$orderId}: " . $e->getMessage());
                }

                $synced++;

                // SP-API rate limit: stay within quota
                sleep(1);
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

    // ─── Fetch buyer info for a single order ──────────────────────

    /**
     * GET /orders/v0/orders/{orderId}/buyerInfo
     *
     * Returns BuyerEmail, BuyerName, BuyerCounty, BuyerTaxInfo etc.
     * Note: Amazon omits email/name when the buyer opts out of sharing.
     */
    private function fetchBuyerInfo(AmazonAccount $account, string $orderId): array
    {
        $response = $this->spApiGet(
            $account,
            self::ORDERS_API_PATH . "/{$orderId}/buyerInfo"
        );

        if ($response->failed()) {
            throw new \Exception($response->body());
        }

        return $response->json()['payload'] ?? [];
    }

    // ─── Fetch order items for a single order ─────────────────────

    /**
     * GET /orders/v0/orders/{orderId}/orderItems
     *
     * Returns ASIN, SKU, title, qty, pricing etc.
     * Handles NextToken pagination for orders with many items.
     */
    private function fetchOrderItems(AmazonAccount $account, string $orderId): array
    {
        $items     = [];
        $nextToken = null;

        do {
            $params   = $nextToken ? ['NextToken' => $nextToken] : [];
            $response = $this->spApiGet(
                $account,
                self::ORDERS_API_PATH . "/{$orderId}/orderItems",
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
        $accessToken = $this->oauthService->getValidAccessToken($account);
        $endpoint    = $this->getEndpointForMarketplace(
            $account->marketplace_id ?? config('amazon-sp-api.default_marketplace_id')
        );

        return Http::withHeaders([
            'x-amz-access-token' => $accessToken,
            'Content-Type'       => 'application/json',
        ])->get("{$endpoint}{$path}", $params);
    }

    // ─── Upsert a single order ────────────────────────────────────

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
                'buyer_name'               => $buyerInfo['BuyerName'] ?? null,
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

            // Fetch & parse customization ZIP if present
            $customizationData = null;
            if ($customizationUrl) {
                $customizationData = $this->customizationService->fetchAndParse($customizationUrl);
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