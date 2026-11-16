<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Also covers promos created after the backfill but before this migration runs.
        DB::table('trader_promos')->whereNull('combines_with')->where('is_combinable', false)->update(['combines_with' => '[]']);
        DB::table('trader_promos')->whereNull('combines_with')->where('is_combinable', true)->update(['combines_with' => '["line","order","shipping","payment"]']);

        Schema::table('trader_promos', function (Blueprint $table): void {
            $table->json('combines_with')->nullable(false)->change();
            $table->dropColumn('is_combinable');
        });
    }

    public function down(): void
    {
        Schema::table('trader_promos', function (Blueprint $table): void {
            $table->boolean('is_combinable')->default(false);
        });

        foreach (DB::table('trader_promos')->select('promo_id', 'combines_with')->cursor() as $promo) {
            DB::table('trader_promos')->where('promo_id', $promo->promo_id)->update([
                'is_combinable' => json_decode($promo->combines_with, true, flags: JSON_THROW_ON_ERROR) !== [],
            ]);
        }

        Schema::table('trader_promos', function (Blueprint $table): void {
            $table->json('combines_with')->nullable()->change();
        });
    }
};
