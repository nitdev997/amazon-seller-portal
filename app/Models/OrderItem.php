<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'order_id',
        'order_item_id',
        'asin',
        'seller_sku',
        'title',
        'quantity_ordered',
        'quantity_shipped',
        'item_price',
        'item_tax',
        'shipping_price',
        'shipping_tax',
        'promotion_discount',
        'currency_code',
        'customization_url',
        'customization_data',
        'raw_data',
    ];

    protected $casts = [
        'item_price'         => 'decimal:2',
        'item_tax'           => 'decimal:2',
        'shipping_price'     => 'decimal:2',
        'shipping_tax'       => 'decimal:2',
        'promotion_discount' => 'decimal:2',
        'customization_data' => 'array',
        'raw_data'           => 'array',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function hasCustomization(): bool
    {
        return !empty($this->customization_data);
    }
}