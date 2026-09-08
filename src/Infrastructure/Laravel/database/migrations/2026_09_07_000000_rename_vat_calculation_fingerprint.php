<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('trader_orders', 'vat_calculation_fingerprint') || Schema::hasColumn('trader_orders', 'pricing_fingerprint')) {
            return;
        }

        Schema::table('trader_orders', function (Blueprint $table): void {
            $table->renameColumn('vat_calculation_fingerprint', 'pricing_fingerprint');
        });

        Schema::table('trader_orders', function (Blueprint $table): void {
            $table->string('pricing_fingerprint', 96)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('trader_orders', 'pricing_fingerprint') || Schema::hasColumn('trader_orders', 'vat_calculation_fingerprint')) {
            return;
        }

        Schema::table('trader_orders', function (Blueprint $table): void {
            $table->renameColumn('pricing_fingerprint', 'vat_calculation_fingerprint');
        });
    }
};
