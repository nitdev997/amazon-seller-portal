<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // Amazon identifiers
            $table->string('order_item_id')->nullable();
            $table->string('asin')->nullable();
            $table->string('seller_sku')->nullable();

            // Product info
            $table->string('title')->nullable();
            $table->integer('quantity_ordered')->default(0);
            $table->integer('quantity_shipped')->default(0);

            // Pricing
            $table->decimal('item_price', 12, 2)->nullable();
            $table->decimal('item_tax', 12, 2)->nullable();
            $table->decimal('shipping_price', 12, 2)->nullable();
            $table->decimal('shipping_tax', 12, 2)->nullable();
            $table->decimal('promotion_discount', 12, 2)->nullable();
            $table->string('currency_code', 3)->nullable();

            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'order_id']);
            $table->index('asin');
            $table->index('seller_sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
