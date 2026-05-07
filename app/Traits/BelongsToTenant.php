<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Automatically scopes all queries to the current tenant.
 * Apply this trait to any model that has a tenant_id column.
 *
 * Do NOT apply to the User model — it IS the auth model and would
 * cause a circular loop: auth()->user() → User boot → auth()->user() → ∞
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Auto-set tenant_id on create
        static::creating(function ($model) {
            if (auth()->check() && empty($model->tenant_id)) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        // Auto-scope all queries — only when a user is authenticated
        static::addGlobalScope('tenant', function (Builder $builder) {
            // Guard: do nothing if no session / running in CLI / unauthenticated
            if (!app()->runningInConsole() && auth()->check()) {
                $builder->where(
                    $builder->getModel()->getTable() . '.tenant_id',
                    auth()->user()->tenant_id
                );
            }
        });
    }

    // ─── Relationship ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ─── Scope helpers ────────────────────────────────────────────

    /**
     * Bypass the tenant scope (admin / CLI operations only).
     */
    public static function allTenants(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }

    /**
     * Scope to a specific tenant explicitly.
     */
    public static function forTenant(int|Tenant $tenant): Builder
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return static::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId);
    }
}