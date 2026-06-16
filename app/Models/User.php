<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    // NOTE: Do NOT use BelongsToTenant here.
    // User IS the auth model — adding that trait causes a circular loop:
    // auth()->user() → loads User → global scope calls auth()->user() → ∞
    use Notifiable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'is_super_admin',
        'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
        'is_super_admin'    => 'boolean',
        'password'          => 'hashed',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ─── Role helpers ─────────────────────────────────────────────

    public function isOwner(): bool       { return $this->role === 'owner'; }
    public function isAdmin(): bool       { return in_array($this->role, ['owner', 'admin']); }
    public function isMember(): bool      { return $this->role === 'member'; }

    /**
     * Platform-level super admin — distinct from tenant-level "owner".
     * Super admins can manage users and view orders across ALL tenants.
     */
    public function isSuperAdmin(): bool  { return (bool) $this->is_super_admin; }
}