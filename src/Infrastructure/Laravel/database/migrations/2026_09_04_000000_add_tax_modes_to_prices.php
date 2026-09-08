<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('trader_shipping_profile_tariffs', 'tax_mode')) {
            Schema::table('trader_shipping_profile_tariffs', function (Blueprint $table): void {
                $table->string('tax_mode')->default(TaxMode::Exclusive->value);
            });
        }

        if (! Schema::hasColumn('trader_payment_methods', 'tax_mode')) {
            Schema::table('trader_payment_methods', function (Blueprint $table): void {
                $table->string('tax_mode')->default(TaxMode::Exclusive->value);
            });
        }

        $this->addServiceAllocationColumns('trader_order_shipping');
        $this->addServiceAllocationColumns('trader_order_payment');

        if (! Schema::hasColumn('trader_order_discounts', 'tax_mode')) {
            Schema::table('trader_order_discounts', function (Blueprint $table): void {
                $table->string('tax_mode')->default(TaxMode::Exclusive->value);
            });

            DB::table('trader_order_discounts')
                ->whereNotNull('total_incl')
                ->update(['tax_mode' => TaxMode::Inclusive->value]);
        }

        if (! Schema::hasColumn('trader_orders', 'pricing_fingerprint')) {
            Schema::table('trader_orders', function (Blueprint $table): void {
                $table->string('pricing_fingerprint', 96)->nullable();
            });
        }
    }

    public function down(): void
    {
        $this->dropColumnIfExists('trader_orders', 'pricing_fingerprint');

        foreach (['trader_order_shipping', 'trader_order_payment'] as $tableName) {
            foreach (['cost_incl', 'discount_incl', 'total_incl', 'cost_tax_mode'] as $column) {
                $this->dropColumnIfExists($tableName, $column);
            }
        }

        $this->dropColumnIfExists('trader_order_discounts', 'tax_mode');
        $this->dropColumnIfExists('trader_payment_methods', 'tax_mode');
        $this->dropColumnIfExists('trader_shipping_profile_tariffs', 'tax_mode');
    }

    private function addServiceAllocationColumns(string $tableName): void
    {
        $columns = [
            'cost_incl' => fn (Blueprint $table) => $table->unsignedBigInteger('cost_incl')->nullable()->after('cost_excl'),
            'discount_incl' => fn (Blueprint $table) => $table->unsignedBigInteger('discount_incl')->nullable()->after('discount_excl'),
            'total_incl' => fn (Blueprint $table) => $table->unsignedBigInteger('total_incl')->nullable()->after('total_excl'),
            'cost_tax_mode' => fn (Blueprint $table) => $table->string('cost_tax_mode')->default(TaxMode::Exclusive->value),
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn($tableName, $column)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($definition): void {
                $definition($table);
            });
        }
    }

    private function dropColumnIfExists(string $tableName, string $column): void
    {
        if (! Schema::hasColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column): void {
            $table->dropColumn($column);
        });
    }
};
