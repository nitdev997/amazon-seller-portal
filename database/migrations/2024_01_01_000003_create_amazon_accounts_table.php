<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amazon_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Amazon SP-API identifiers
            $table->string('seller_id')->nullable();          // e.g. A1B2C3D4E5F6G7
            $table->string('marketplace_id')->nullable();     // e.g. ATVPDKIKX0DER (US)
            $table->string('marketplace_name')->nullable();   // e.g. Amazon.com

            // OAuth tokens (encrypted at rest)
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('access_token_expires_at')->nullable();

            // LWA (Login with Amazon) App credentials (per-tenant or global)
            $table->string('lwa_client_id')->nullable();
            $table->text('lwa_client_secret')->nullable();

            // Status
            $table->string('status')->default('disconnected'); // connected, disconnected, error
            $table->string('error_message')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('seller_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amazon_accounts');
    }
};
