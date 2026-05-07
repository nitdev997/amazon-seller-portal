<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amazon_account_id')->constrained()->cascadeOnDelete();

            // Amazon identifiers
            $table->string('amazon_order_id')->unique(); // e.g. 123-1234567-1234567
            $table->string('marketplace_id')->nullable();
            $table->string('seller_order_id')->nullable();

            // Status
            $table->string('order_status');
            // Pending, Unshipped, PartiallyShipped, Shipped, Canceled, Unfulfillable

            $table->string('fulfillment_channel')->nullable(); // MFN or AFN
            $table->string('sales_channel')->nullable();       // Amazon.com, etc.
            $table->boolean('is_business_order')->default(false);
            $table->boolean('is_prime')->default(false);
            $table->boolean('is_replacement_order')->default(false);

            // Buyer
            $table->string('buyer_email')->nullable();
            $table->string('buyer_name')->nullable();

            // Financials
            $table->decimal('order_total', 12, 2)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->integer('number_of_items_shipped')->default(0);
            $table->integer('number_of_items_unshipped')->default(0);

            // Dates
            $table->timestamp('purchase_date')->nullable();
            $table->timestamp('last_update_date')->nullable();
            $table->timestamp('earliest_ship_date')->nullable();
            $table->timestamp('latest_ship_date')->nullable();
            $table->timestamp('earliest_delivery_date')->nullable();
            $table->timestamp('latest_delivery_date')->nullable();

            // Shipping address (JSON for flexibility)
            $table->json('shipping_address')->nullable();

            // Raw payload from Amazon for reference
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'order_status']);
            $table->index(['tenant_id', 'purchase_date']);
            $table->index(['tenant_id', 'amazon_account_id']);
            $table->index('amazon_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
