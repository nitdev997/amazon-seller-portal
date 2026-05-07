<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'amazon_account_id',
        'amazon_order_id',
        'marketplace_id',
        'seller_order_id',
        'order_status',
        'fulfillment_channel',
        'sales_channel',
        'is_business_order',
        'is_prime',
        'is_replacement_order',
        'buyer_email',
        'buyer_name',
        'order_total',
        'currency_code',
        'number_of_items_shipped',
        'number_of_items_unshipped',
        'purchase_date',
        'last_update_date',
        'earliest_ship_date',
        'latest_ship_date',
        'earliest_delivery_date',
        'latest_delivery_date',
        'shipping_address',
        'raw_data',
    ];

    protected $casts = [
        'is_business_order'    => 'boolean',
        'is_prime'             => 'boolean',
        'is_replacement_order' => 'boolean',
        'order_total'          => 'decimal:2',
        'purchase_date'        => 'datetime',
        'last_update_date'     => 'datetime',
        'earliest_ship_date'   => 'datetime',
        'latest_ship_date'     => 'datetime',
        'earliest_delivery_date' => 'datetime',
        'latest_delivery_date' => 'datetime',
        'shipping_address'     => 'array',
        'raw_data'             => 'array',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function amazonAccount(): BelongsTo
    {
        return $this->belongsTo(AmazonAccount::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ─── Status badge helper ──────────────────────────────────────

    public function statusColor(): string
    {
        return match ($this->order_status) {
            'Shipped'           => 'green',
            'Unshipped'         => 'yellow',
            'PartiallyShipped'  => 'blue',
            'Pending'           => 'gray',
            'Canceled'          => 'red',
            'Unfulfillable'     => 'red',
            default             => 'gray',
        };
    }
}
