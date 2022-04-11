<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBasicTraderTables extends Migration
{
    const PREFIX = 'trader_';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->upCatalog();
        $this->upServices();
        $this->upCustomers();
        $this->upOrders();
    }

    private function upCatalog()
    {
        Schema::create(static::PREFIX.'products', function (Blueprint $table)
        {
            $table->char('product_id', 36)->primary();
            $table->string('state')->default(\Thinktomorrow\Trader\Domain\Model\Product\ProductState::draft->value);
            $table->json('data')->nullable(); // Contains generic product data like label, description
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();
        });

        Schema::create(static::PREFIX.'product_variants', function (Blueprint $table)
        {
            $table->char('variant_id', 36)->primary();
            $table->char('product_id', 36);
            $table->boolean('show_in_grid')->default(0);
            $table->string('state')->default(\Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState::available->value);
            $table->integer('sale_price')->unsigned();
            $table->integer('unit_price')->unsigned();
            $table->char('tax_rate', 3);
            $table->boolean('includes_vat');
//            $table->json('options')->nullable(); // variant options
            $table->json('data')->nullable();
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();

            $table->index('product_id');
            $table->foreign('product_id')->references('product_id')->on(static::PREFIX.'products')->onDelete('cascade');
        });

        Schema::create(static::PREFIX.'product_options', function (Blueprint $table) {
            $table->char('option_id', 36);
            $table->char('product_id', 36);
            $table->json('data')->nullable();
            $table->unsignedInteger('order_column')->default(0);

            $table->foreign('product_id')->references('product_id')->on('trader_products')->onDelete('cascade');
        });

        Schema::create(static::PREFIX.'product_option_values', function (Blueprint $table) {
            $table->char('option_id', 36);
            $table->char('option_value_id', 36);
            $table->json('data')->nullable();
            $table->unsignedInteger('order_column')->default(0);

            $table->foreign('option_id')->references('option_id')->on('trader_product_options')->onDelete('cascade');
        });

        Schema::create(static::PREFIX.'variant_option_values', function (Blueprint $table) {
            $table->char('variant_id', 36);
            $table->char('option_value_id', 36);
            $table->unsignedInteger('order_column')->default(0);

            $table->foreign('variant_id')->references('variant_id')->on('trader_product_variants')->onDelete('cascade');
            $table->foreign('option_value_id')->references('option_value_id')->on('trader_product_option_values')->onDelete('cascade');
        });

        Schema::create(static::PREFIX.'taxa', function (Blueprint $table)
        {
            $table->char('taxon_id', 36)->primary();
            $table->char('parent_id', 36)->nullable()->index();
            $table->string('key')->unique();
            $table->string('state')->default(\Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState::online->value);
            $table->json('data')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create(static::PREFIX.'taxa_products', function (Blueprint $table) {
            $table->char('taxon_id', 36);
            $table->char('product_id', 36);

            $table->foreign('taxon_id')->references('taxon_id')->on(static::PREFIX.'taxa')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on(static::PREFIX.'products')->onDelete('cascade');

            $table->unique(['taxon_id','product_id']);
        });

        Schema::create(static::PREFIX.'redirects', function (Blueprint $table)
        {
            $table->id();
            $table->string('from');
            $table->string('to');
        });
    }

    private function upServices()
    {
        Schema::create(static::PREFIX.'shipping_profiles', function (Blueprint $table)
        {
            $table->char('shipping_profile_id', 36)->primary();
            $table->string('label');
            $table->json('data')->nullable();
            $table->boolean('active')->default(1);
            $table->unsignedInteger('order_column')->default(0);
        });

        Schema::create(static::PREFIX.'shipping_profile_tariffs', function (Blueprint $table)
        {
            $table->char('tariff_id', 36)->primary();
            $table->char('shipping_profile_id', 36);
            $table->integer('rate')->unsigned();
            $table->integer('from')->unsigned();
            $table->integer('to')->unsigned();
            $table->unsignedInteger('order_column')->default(0);

            $table->index('shipping_profile_id');
            $table->foreign('shipping_profile_id')->references('shipping_profile_id')->on(static::PREFIX.'shipping_profiles')->onDelete('cascade');
        });

        Schema::create(static::PREFIX.'payment_methods', function (Blueprint $table)
        {
            $table->char('payment_method_id', 36)->primary();
            $table->string('label');
            $table->integer('rate')->unsigned();
            $table->json('data')->nullable();
            $table->boolean('active')->default(1);
            $table->unsignedInteger('order_column')->default(0);
        });
    }

    private function upCustomers()
    {
        Schema::create(static::PREFIX . 'customers', function (Blueprint $table) {
            $table->char('customer_id', 36)->primary();
            $table->boolean('active')->default(1);
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('phone')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create(static::PREFIX . 'customer_password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    private function upOrders()
    {
        Schema::create(static::PREFIX.'orders', function (Blueprint $table)
        {
            $table->char('order_id', 36)->primary();
            $table->string('order_state', 32);

            $table->integer('total')->unsigned();
            $table->boolean('includes_vat');
            $table->integer('tax_total')->unsigned();
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('data')->nullable();
        });

        Schema::create(static::PREFIX.'order_lines', function (Blueprint $table)
        {
            $table->char('line_id', 36)->primary();
            $table->char('order_id', 36);
            $table->char('variant_id', 36); // reference to original/current product
            $table->smallInteger('quantity')->unsigned();
            $table->integer('line_price')->unsigned();
            $table->string('tax_rate');
            $table->boolean('includes_vat');
            $table->json('data')->nullable(); // Contains historic product data like name

            $table->index('product_id');
            $table->index('order_id');
            $table->foreign('order_id')->references('order_id')->on(static::PREFIX.'orders')->onDelete('cascade');
        });

        Schema::create(static::PREFIX.'order_shoppers', function (Blueprint $table)
        {
            $table->char('shopper_id', 36)->primary();
            $table->char('order_id', 36);
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('email');
            $table->string('firstname');
            $table->string('lastname');
            $table->boolean('register_after_checkout');
            $table->json('data')->nullable();

            $table->index('order_id');
            $table->foreign('order_id')->references('order_id')->on(static::PREFIX.'orders')->onDelete('cascade');
        });

        Schema::create(static::PREFIX.'order_shipping', function (Blueprint $table)
        {
            $table->char('shipping_id', 36)->primary();
            $table->char('order_id', 36);
            $table->char('shipping_profile_id', 36);
            $table->string('shipping_state', 32);

            $table->integer('cost')->unsigned();
            $table->string('tax_rate');
            $table->boolean('includes_vat');
            $table->json('data')->nullable();

            $table->index('order_id');
            $table->foreign('order_id')->references('order_id')->on(static::PREFIX.'orders')->onDelete('cascade');
        });

        Schema::create(static::PREFIX.'order_payment', function (Blueprint $table)
        {
            $table->char('payment_id', 36)->primary();
            $table->char('order_id', 36);
            $table->char('payment_method_id', 36);
            $table->string('payment_state', 32);

            $table->integer('cost')->unsigned();
            $table->string('tax_rate');
            $table->boolean('includes_vat');
            $table->json('data')->nullable();

            $table->index('order_id');
            $table->foreign('order_id')->references('order_id')->on(static::PREFIX.'orders')->onDelete('cascade');
        });

        Schema::create(static::PREFIX.'order_events', function (Blueprint $table)
        {
            $table->id();
            $table->char('order_id', 36);
            $table->string('event'); // transition.confirmed, transition.paid, notification.delay
            $table->dateTime('at');
            $table->json('data')->nullable();

            $table->index('order_id');
            $table->foreign('order_id')->references('order_id')->on(static::PREFIX.'orders')->onDelete('cascade');
        });

        Schema::create(static::PREFIX.'order_discounts', function (Blueprint $table)
        {
            $table->char('discount_id', 36)->primary();
            $table->char('order_id', 36);
            $table->char('promo_id', 36)->nullable(); // Refers to original promo
            $table->integer('total')->unsigned();
            $table->string('tax_rate');
            $table->boolean('includes_vat');
            $table->json('data')->nullable();

            $table->index('order_id');
            $table->foreign('order_id')->references('order_id')->on(static::PREFIX.'orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(static::PREFIX.'order_discounts');
        Schema::dropIfExists(static::PREFIX.'order_events');
        Schema::dropIfExists(static::PREFIX.'order_payment');
        Schema::dropIfExists(static::PREFIX.'order_shipping');
        Schema::dropIfExists(static::PREFIX.'order_customer');
        Schema::dropIfExists(static::PREFIX.'order_lines');
        Schema::dropIfExists(static::PREFIX.'orders');

        Schema::dropIfExists(static::PREFIX.'shipping_profiles');
        Schema::dropIfExists(static::PREFIX.'shipping_profile_tariffs');
        Schema::dropIfExists(static::PREFIX.'payment_methods');

        Schema::dropIfExists(static::PREFIX.'customers');

        Schema::dropIfExists(static::PREFIX.'taxa_products');
        Schema::dropIfExists(static::PREFIX.'taxa');
        Schema::dropIfExists(static::PREFIX.'product_variants');
        Schema::dropIfExists(static::PREFIX.'products');
    }
}
