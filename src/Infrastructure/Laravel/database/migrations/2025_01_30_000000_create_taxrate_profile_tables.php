<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileState;

return new class extends Migration {
    const PREFIX = 'trader_';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(static::PREFIX.'taxrate_profiles', function (Blueprint $table) {
            $table->char('taxrate_profile_id', 36)->primary();
            $table->json('data')->nullable();
            $table->string('state')->default(TaxRateProfileState::online->value);
            $table->unsignedInteger('order_column')->default(0);
        });

        Schema::create(static::PREFIX.'taxrate_profile_countries', function (Blueprint $table) {
            $table->char('taxrate_profile_id', 36);
            $table->char('country_id', 2);

            $table->primary(['taxrate_profile_id', 'country_id'], 'trader_taxrate_profile_id_country_id_primary');
            $table->foreign('taxrate_profile_id')->references('taxrate_profile_id')->on(static::PREFIX.'taxrate_profiles')->onDelete('cascade');
            $table->foreign('country_id')->references('country_id')->on(static::PREFIX.'countries')->onDelete('cascade');
        });

        Schema::create(static::PREFIX.'taxrate_profile_doubles', function (Blueprint $table) {
            $table->char('taxrate_double_id', 36)->primary();
            $table->char('taxrate_profile_id', 36);
            $table->char('original_rate', 3);
            $table->char('rate', 3);

            $table->index('taxrate_profile_id');
            $table->foreign('taxrate_profile_id')->references('taxrate_profile_id')->on(static::PREFIX.'taxrate_profiles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(static::PREFIX.'taxrate_profiles');
        Schema::dropIfExists(static::PREFIX.'taxrate_profile_countries');
        Schema::dropIfExists(static::PREFIX.'taxrate_profile_doubles');
    }
};
