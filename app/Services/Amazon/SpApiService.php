<?php

namespace App\Services\Amazon;

use App\Models\AmazonAccount;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wraps the Amazon SP-API Orders API.
 *
 * Docs: https://developer-docs.amazon.com/sp-api/docs/orders-api-v0-reference
 *
 * Note: SP-API uses AWS Signature Version 4 (SigV4) for request signing.
 * For production, use a library like: jlevers/selling-partner-api
 * This service shows the raw HTTP approach for clarity.
 */
class SpApiService
{
    private const ORDERS_API_PATH = '/orders/v0/orders';

    public function __construct(
        private readonly SpApiOAuthService $oauthService
    ) {}

    // ─── Sync orders ──────────────────────────────────────────────

    /**
     * Fetch orders from Amazon and upsert them into the database.
     *
     * @param  AmazonAccount  $account
     * @param  Carbon|null    $createdAfter  Defaults to last 30 days
     * @return int  Number of orders synced
     */
    public function syncOrders(AmazonAccount $account, ?Carbon $createdAfter = null): int
    {
        $createdAfter ??= now()->subDays(30);
        $synced = 0;
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
                $this->upsertOrder($account, $amazonOrder);
                $synced++;
            }

            $nextToken = $payload['NextToken'] ?? null;

        } while ($nextToken);

        $account->update(['last_synced_at' => now()]);

        return $synced;
    }

    // ─── Fetch a single page of orders ───────────────────────────

    private function fetchOrderPage(
        AmazonAccount $account,
        Carbon $createdAfter,
        ?string $nextToken = null
    ): array {
        $accessToken = $this->oauthService->getValidAccessToken($account);

        $params = [
            'MarketplaceIds' => $account->marketplace_id ?? config('amazon-sp-api.default_marketplace_id'),
            'CreatedAfter'   => $createdAfter->utc()->format('Y-m-d\TH:i:s\Z'),
        ];

        if ($nextToken) {
            $params['NextToken'] = $nextToken;
        }

        // NOTE: In production you must sign requests with AWS SigV4.
        // Use the `jlevers/selling-partner-api` package which handles this.
        // Here we show the raw HTTP call structure for illustration.
        $endpoint = $this->getEndpointForMarketplace($account->marketplace_id ?? config('amazon-sp-api.default_marketplace_id'));

        $response = Http::withHeaders([
            'x-amz-access-token' => $accessToken,
            'Content-Type'       => 'application/json',
        ])->get("{$endpoint}" . self::ORDERS_API_PATH, $params);

        if ($response->failed()) {
            Log::error('SP-API Orders fetch failed', [
                'account_id' => $account->id,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);

            throw new \Exception('Amazon API error: ' . $response->body());
        }

        return $response->json();
    }

    // ─── Upsert a single order ────────────────────────────────────

    private function upsertOrder(AmazonAccount $account, array $data): void
    {
        $shippingAddress = $data['ShippingAddress'] ?? null;
        $orderTotal      = $data['OrderTotal'] ?? null;

        $order = Order::withoutGlobalScope('tenant')->updateOrCreate(
            ['amazon_order_id' => $data['AmazonOrderId']],
            [
                'tenant_id'                => $account->tenant_id,
                'amazon_account_id'        => $account->id,
                'marketplace_id'           => $data['MarketplaceId'] ?? null,
                'seller_order_id'          => $data['SellerOrderId'] ?? null,
                'order_status'             => $data['OrderStatus'],
                'fulfillment_channel'      => $data['FulfillmentChannel'] ?? null,
                'sales_channel'            => $data['SalesChannel'] ?? null,
                'is_business_order'        => $data['IsBusinessOrder'] ?? false,
                'is_prime'                 => $data['IsPrime'] ?? false,
                'is_replacement_order'     => $data['IsReplacementOrder'] ?? false,
                'buyer_email'              => $data['BuyerInfo']['BuyerEmail'] ?? null,
                'buyer_name'               => $data['BuyerInfo']['BuyerName'] ?? null,
                'order_total'              => $orderTotal ? $orderTotal['Amount'] : null,
                'currency_code'            => $orderTotal ? $orderTotal['CurrencyCode'] : null,
                'number_of_items_shipped'  => $data['NumberOfItemsShipped'] ?? 0,
                'number_of_items_unshipped'=> $data['NumberOfItemsUnshipped'] ?? 0,
                'purchase_date'            => $data['PurchaseDate'] ?? null,
                'last_update_date'         => $data['LastUpdateDate'] ?? null,
                'earliest_ship_date'       => $data['EarliestShipDate'] ?? null,
                'latest_ship_date'         => $data['LatestShipDate'] ?? null,
                'earliest_delivery_date'   => $data['EarliestDeliveryDate'] ?? null,
                'latest_delivery_date'     => $data['LatestDeliveryDate'] ?? null,
                'shipping_address'         => $shippingAddress,
                'raw_data'                 => $data,
            ]
        );

        // Sync order items if present
        if (! empty($data['OrderItems'])) {
            $this->upsertOrderItems($order, $data['OrderItems']);
        }
    }

    // ─── Upsert order items ───────────────────────────────────────

    private function upsertOrderItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $itemPrice = $item['ItemPrice'] ?? null;
            $itemTax   = $item['ItemTax'] ?? null;
            $shipping  = $item['ShippingPrice'] ?? null;

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
                    'raw_data'           => $item,
                ]
            );
        }
    }

    // ─── Marketplace endpoint map ─────────────────────────────────

    /**
     * SP-API has region-specific endpoints.
     * https://developer-docs.amazon.com/sp-api/docs/sp-api-endpoints
     */
    private function getEndpointForMarketplace(?string $marketplaceId): string
    {
        return match (true) {
            // North America
            in_array($marketplaceId, ['ATVPDKIKX0DER', 'A2EUQ1WTGCTBG2', 'A1AM78C64UM0Y8'])
                => 'https://sellingpartnerapi-na.amazon.com',
            // Europe
            in_array($marketplaceId, ['A1F83G8C2ARO7P', 'A1PA6795UKMFR9', 'APJ6JRA9NG5V4', 'A13V1IB3VIYZZH'])
                => 'https://sellingpartnerapi-eu.amazon.com',
            // Far East
            in_array($marketplaceId, ['A1VC38T7YXB528', 'AAHKV2X7AFYLW'])
                => 'https://sellingpartnerapi-fe.amazon.com',
            default
                => 'https://sellingpartnerapi-na.amazon.com',
        };
    }
}