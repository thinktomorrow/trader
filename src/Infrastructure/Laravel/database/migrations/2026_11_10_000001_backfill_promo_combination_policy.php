<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('trader_promos')->whereNull('combines_with')->where('is_combinable', false)->update(['combines_with' => '[]']);
        DB::table('trader_promos')->whereNull('combines_with')->where('is_combinable', true)->update(['combines_with' => '["line","order","shipping","payment"]']);
        DB::table('trader_promo_discounts')->where('key', 'sale_price')->update(['discountable_type' => 'line']);
        DB::table('trader_promo_discounts')->whereIn('key', ['fixed_amount', 'percentage_off'])->update(['discountable_type' => 'order']);
    }

    public function down(): void
    {
        // A rollback cannot distinguish backfilled rows from policies edited later.
    }
};
