<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trader_promos', function (Blueprint $table): void {
            $table->json('combines_with')->nullable();
            $table->boolean('multiple_product_discounts_per_line')->default(false);
        });

        Schema::table('trader_promo_discounts', function (Blueprint $table): void {
            $table->string('discountable_type', 16)->nullable();
        });

    }

    public function down(): void
    {
        Schema::table('trader_promo_discounts', function (Blueprint $table): void {
            $table->dropColumn('discountable_type');
        });

        Schema::table('trader_promos', function (Blueprint $table): void {
            $table->dropColumn(['combines_with', 'multiple_product_discounts_per_line']);
        });
    }
};
