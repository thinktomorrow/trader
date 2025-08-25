<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    const PREFIX = 'trader_';

    public function up()
    {
        Schema::create(static::PREFIX . 'taxonomies', function (Blueprint $table) {
            $table->char('taxonomy_id', 36)->primary();
            $table->string('type');
            $table->string('state')->default(\Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyState::online->value);
            $table->boolean('shows_as_grid_filter')->default(false);
            $table->boolean('shows_in_grid')->default(false);
            $table->boolean('allows_multiple_values')->default(false);
            $table->boolean('allows_nestable_values')->default(false);
            $table->json('data')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::table(static::PREFIX . 'taxa', function (Blueprint $table) {
            $table->char('taxonomy_id', 36)->after('taxon_id');
        });

        Schema::table(static::PREFIX . 'taxa_products', function (Blueprint $table) {
            $table->string('state')->default(\Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState::online->value);
            $table->json('data')->nullable();
            $table->unsignedInteger('order_column')->default(0);
        });

        Schema::create(static::PREFIX . 'taxa_variants', function (Blueprint $table) {
            $table->char('taxon_id', 36);
            $table->char('variant_id', 36);
            $table->string('state')->default(\Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState::online->value);
            $table->json('data')->nullable();
            $table->unsignedInteger('order_column')->default(0);

            $table->primary(['taxon_id', 'variant_id']);

            $table->foreign('taxon_id')->references('taxon_id')->on(static::PREFIX . 'taxa')->onDelete('cascade');
            $table->foreign('variant_id')->references('variant_id')->on(static::PREFIX . 'product_variants')->onDelete('cascade');
        });

        //        Schema::create(static::PREFIX . 'variant_option_values', function (Blueprint $table) {
        //            $table->char('variant_id', 36);
        //            $table->char('option_value_id', 36);
        //            $table->unsignedInteger('order_column')->default(0);
        //
        //            $table->primary(['variant_id', 'option_value_id']);
        //
        //            $table->foreign('variant_id')->references('variant_id')->on('trader_product_variants')->onDelete('cascade');
        //            $table->foreign('option_value_id')->references('option_value_id')->on('trader_product_option_values')->onDelete('cascade');
        //        });

        // TODO: migrate product_options to taxonomy_products...
        // product_option_values -> taxonomy_products...

        //        Schema::create(static::PREFIX . 'product_options', function (Blueprint $table) {
        //            $table->char('option_id', 36)->primary();
        //            $table->char('product_id', 36);
        //            $table->json('data')->nullable();
        //            $table->unsignedInteger('order_column')->default(0);
        //
        //            $table->foreign('product_id')->references('product_id')->on('trader_products')->onDelete('cascade');
        //        });

        //        Schema::create(static::PREFIX . 'product_option_values', function (Blueprint $table) {
        //            $table->char('option_value_id', 36)->primary();
        //            $table->char('option_id', 36);
        //            $table->json('data')->nullable();
        //            $table->unsignedInteger('order_column')->default(0);
        //        });

        //        Schema::table(static::PREFIX . 'product_option_values', function (Blueprint $table) {
        //            $table->foreign('option_id')->references('option_id')->on('trader_product_options')->onDelete('cascade');
        //        });


    }

    public function down()
    {
        Schema::dropIfExists(static::PREFIX . 'vat_rates');
        Schema::dropIfExists(static::PREFIX . 'vat_base_rates');
    }
};
