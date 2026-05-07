<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'plan',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings'  => 'array',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function amazonAccounts(): HasMany
    {
        return $this->hasMany(AmazonAccount::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function hasConnectedAmazonAccount(): bool
    {
        return $this->amazonAccounts()
            ->where('status', 'connected')
            ->exists();
    }

    public function activeAmazonAccount(): ?AmazonAccount
    {
        return $this->amazonAccounts()
            ->where('status', 'connected')
            ->first();
    }
}
