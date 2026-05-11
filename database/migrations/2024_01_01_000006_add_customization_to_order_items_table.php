<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Raw URL from Amazon BuyerCustomizedInfo.CustomizedURL
            $table->string('customization_url')->nullable()->after('raw_data');

            // Parsed customization fields extracted from the ZIP
            $table->json('customization_data')->nullable()->after('customization_url');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['customization_url', 'customization_data']);
        });
    }
};
