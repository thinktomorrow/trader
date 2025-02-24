<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateState;

return new class extends Migration {
    const PREFIX = 'trader_';

    public function up()
    {
        Schema::create(static::PREFIX . 'vat_rates', function (Blueprint $table) {
            $table->char('vat_rate_id', 36)->primary();
            $table->char('country_id', 2);
            $table->char('rate', 3);
            $table->boolean('is_standard')->default(false);
            $table->json('data')->nullable();
            $table->string('state')->default(VatRateState::online->value);
            $table->unsignedInteger('order_column')->default(0);

            $table->unique(['country_id', 'rate']);
            $table->foreign('country_id')->references('country_id')->on(static::PREFIX . 'countries')->onDelete('cascade');
        });

        Schema::create(static::PREFIX . 'vat_base_rates', function (Blueprint $table) {
            $table->char('base_rate_id', 36)->primary();
            $table->char('origin_vat_rate_id', 36);
            $table->char('target_vat_rate_id', 36);

            $table->foreign('origin_vat_rate_id')->references('vat_rate_id')->on(static::PREFIX . 'vat_rates')->onDelete('cascade');
            $table->foreign('target_vat_rate_id')->references('vat_rate_id')->on(static::PREFIX . 'vat_rates')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists(static::PREFIX . 'vat_rates');
        Schema::dropIfExists(static::PREFIX . 'vat_base_rates');
    }
};
