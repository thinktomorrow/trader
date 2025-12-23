<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trader_orders', function (Blueprint $table) {

            // Totals
            $table->unsignedBigInteger('total_excl')->nullable()->after('order_state');
            $table->unsignedBigInteger('total_incl')->nullable()->after('total_excl');
            $table->unsignedBigInteger('total_vat')->nullable()->after('total_incl');
            $table->json('vat_lines')->nullable()->after('total_vat');

            // Subtotal
            $table->unsignedBigInteger('subtotal_excl')->nullable()->after('total_incl');
            $table->unsignedBigInteger('subtotal_incl')->nullable()->after('subtotal_excl');

            // Discounts
            $table->unsignedBigInteger('discount_total_excl')->nullable()->after('subtotal_incl');
            $table->unsignedBigInteger('discount_total_incl')->nullable()->after('discount_total_excl');

            // Shipping
            $table->unsignedBigInteger('shipping_cost_excl')->nullable()->after('discount_total_incl');
            $table->unsignedBigInteger('shipping_cost_incl')->nullable()->after('shipping_cost_excl');

            // Payment
            $table->unsignedBigInteger('payment_cost_excl')->nullable()->after('shipping_cost_incl');
            $table->unsignedBigInteger('payment_cost_incl')->nullable()->after('payment_cost_excl');
        });
    }

    public function down(): void
    {
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal_excl',
                'subtotal_incl',
                'discount_total_excl',
                'discount_total_incl',
                'shipping_cost_excl',
                'shipping_cost_incl',
                'payment_cost_excl',
                'payment_cost_incl',
                'total_excl',
                'total_incl',
                'total_vat',
                'vat_lines',
            ]);
        });
    }
};

