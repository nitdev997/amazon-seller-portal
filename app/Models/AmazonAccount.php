<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class AmazonAccount extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'seller_id',
        'marketplace_id',
        'marketplace_name',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'lwa_client_id',
        'lwa_client_secret',
        'status',
        'error_message',
        'last_synced_at',
    ];

    protected $casts = [
        'access_token_expires_at' => 'datetime',
        'last_synced_at'          => 'datetime',
    ];

    /**
     * Columns that should be encrypted in the database.
     * Requires: composer require spatie/laravel-model-encryption (optional)
     * or use Laravel's built-in encrypted cast below.
     */
    protected $encryptable = [
        'access_token',
        'refresh_token',
        'lwa_client_secret',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ─── Status helpers ───────────────────────────────────────────

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function isAccessTokenExpired(): bool
    {
        if (! $this->access_token_expires_at) {
            return true;
        }

        return Carbon::now()->isAfter(
            $this->access_token_expires_at->subMinutes(5) // 5 min buffer
        );
    }

    public function markAsConnected(string $sellerId, string $marketplaceId, string $marketplaceName): void
    {
        $this->update([
            'seller_id'        => $sellerId,
            'marketplace_id'   => $marketplaceId,
            'marketplace_name' => $marketplaceName,
            'status'           => 'connected',
            'error_message'    => null,
        ]);
    }

    public function markAsError(string $message): void
    {
        $this->update([
            'status'        => 'error',
            'error_message' => $message,
        ]);
    }
}
