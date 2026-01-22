<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('trader_orders', 'total_excl')) {
            return;
        }

        Schema::table('trader_orders', function (Blueprint $table) {

            // Totals
            $table->unsignedBigInteger('total_excl')->nullable()->after('order_state');
            $table->unsignedBigInteger('total_incl')->nullable()->after('total_excl');
            $table->unsignedBigInteger('total_vat')->nullable()->after('total_incl');
            $table->json('vat_lines')->nullable()->after('total_vat');

            // Subtotal
            $table->unsignedBigInteger('subtotal_excl')->nullable()->after('vat_lines');
            $table->unsignedBigInteger('subtotal_incl')->nullable()->after('subtotal_excl');

            // Discounts
            $table->unsignedBigInteger('discount_excl')->nullable()->after('subtotal_incl');
            $table->unsignedBigInteger('discount_incl')->nullable()->after('discount_excl');

            // Shipping
            $table->unsignedBigInteger('shipping_cost_excl')->nullable()->after('discount_incl');
            $table->unsignedBigInteger('shipping_cost_incl')->nullable()->after('shipping_cost_excl');

            // Payment
            $table->unsignedBigInteger('payment_cost_excl')->nullable()->after('shipping_cost_incl');
            $table->unsignedBigInteger('payment_cost_incl')->nullable()->after('payment_cost_excl');
        });

        Schema::table('trader_order_lines', function (Blueprint $table) {
            $table->bigInteger('unit_price_excl')->unsigned()->nullable();
            $table->bigInteger('unit_price_incl')->unsigned()->nullable();

            $table->bigInteger('total_excl')->unsigned()->nullable();
            $table->bigInteger('total_vat')->unsigned()->nullable();
            $table->bigInteger('total_incl')->unsigned()->nullable();

            $table->bigInteger('discount_excl')->unsigned()->default(0);
            $table->bigInteger('discount_incl')->unsigned()->default(0);
        });

        Schema::table('trader_order_shipping', function (Blueprint $table) {
            $table->bigInteger('cost_excl')->unsigned()->nullable();
            $table->bigInteger('total_excl')->unsigned()->nullable();
            $table->bigInteger('discount_excl')->unsigned()->default(0);
        });

        Schema::table('trader_order_payment', function (Blueprint $table) {
            $table->bigInteger('cost_excl')->unsigned()->nullable();
            $table->bigInteger('total_excl')->unsigned()->nullable();
            $table->bigInteger('discount_excl')->unsigned()->default(0);
        });

        Schema::table('trader_order_discounts', function (Blueprint $table) {
            $table->integer('total_excl')->unsignedBigInteger();
            $table->integer('total_incl')->unsignedBigInteger()->nullable();
            $table->string('vat_rate')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal_excl',
                'subtotal_incl',
                'discount_excl',
                'discount_incl',
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
