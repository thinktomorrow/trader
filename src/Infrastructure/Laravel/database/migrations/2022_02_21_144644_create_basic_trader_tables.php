<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
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
        $this->upPromos();
        $this->upOrders();
    }

    private function upCatalog()
    {
        Schema::create(static::PREFIX . 'products', function (Blueprint $table) {
            $table->char('product_id', 36)->primary();
            $table->string('state')->default(\Thinktomorrow\Trader\Domain\Model\Product\ProductState::offline->value);
            $table->json('data')->nullable(); // Contains generic product data like label, description
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();
        });

        Schema::create(static::PREFIX . 'product_variants', function (Blueprint $table) {
            $table->char('variant_id', 36)->primary();
            $table->char('product_id', 36);
            $table->string('sku')->unique();
            $table->string('ean')->unique()->nullable();
            $table->boolean('show_in_grid')->default(0);
            $table->string('state')->default(\Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState::available->value);
            $table->integer('sale_price')->unsigned();
            $table->integer('unit_price')->unsigned();
            $table->char('tax_rate', 3);
            $table->boolean('includes_vat');
            $table->json('data')->nullable();

            $table->integer('stock_level')->default(0);
            $table->boolean('ignore_out_of_stock')->default(true);
            $table->json('stock_data')->nullable();

            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();

            $table->index('product_id');
            $table->foreign('product_id')->references('product_id')->on(static::PREFIX . 'products')->onDelete('cascade');
        });

        Schema::create(static::PREFIX . 'product_options', function (Blueprint $table) {
            $table->char('option_id', 36)->primary();
            $table->char('product_id', 36);
            $table->json('data')->nullable();
            $table->unsignedInteger('order_column')->default(0);

            $table->foreign('product_id')->references('product_id')->on('trader_products')->onDelete('cascade');
        });

        Schema::create(static::PREFIX . 'product_option_values', function (Blueprint $table) {
            $table->char('option_value_id', 36)->primary();
            $table->char('option_id', 36);
            $table->json('data')->nullable();
            $table->unsignedInteger('order_column')->default(0);
        });

        Schema::table(static::PREFIX . 'product_option_values', function (Blueprint $table) {
            $table->foreign('option_id')->references('option_id')->on('trader_product_options')->onDelete('cascade');
        });

        Schema::create(static::PREFIX . 'variant_option_values', function (Blueprint $table) {
            $table->char('variant_id', 36);
            $table->char('option_value_id', 36);
            $table->unsignedInteger('order_column')->default(0);

            $table->primary(['variant_id', 'option_value_id']);

            $table->foreign('variant_id')->references('variant_id')->on('trader_product_variants')->onDelete('cascade');
            $table->foreign('option_value_id')->references('option_value_id')->on('trader_product_option_values')->onDelete('cascade');
        });

        Schema::create(static::PREFIX . 'product_personalisations', function (Blueprint $table) {
            $table->char('personalisation_id', 36)->primary();
            $table->char('product_id', 36);
            $table->string('personalisation_type');
            $table->json('data')->nullable();
            $table->unsignedInteger('order_column')->default(0);

            $table->foreign('product_id')->references('product_id')->on('trader_products')->onDelete('cascade');
        });

        Schema::create(static::PREFIX . 'taxa', function (Blueprint $table) {
            $table->char('taxon_id', 36)->primary();
            $table->char('parent_id', 36)->nullable()->index();
            $table->string('state')->default(\Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState::online->value);
            $table->json('data')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create(static::PREFIX . 'taxa_keys', function (Blueprint $table) {
            $table->string('key', 255)->primary();
            $table->char('taxon_id', 36)->index();
            $table->string('locale');

            $table->foreign('taxon_id')->references('taxon_id')->on(static::PREFIX . 'taxa')->onDelete('cascade');
        });

        Schema::create(static::PREFIX . 'taxa_products', function (Blueprint $table) {
            $table->char('taxon_id', 36);
            $table->char('product_id', 36);

            $table->primary(['taxon_id', 'product_id']);

            $table->foreign('taxon_id')->references('taxon_id')->on(static::PREFIX . 'taxa')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on(static::PREFIX . 'products')->onDelete('cascade');
        });

        Schema::create(static::PREFIX . 'taxa_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('locale');
            $table->string('from');
            $table->string('to');
            $table->timestamp('created_at');

            $table->unique(['locale', 'from']);
        });
    }

    private function upServices()
    {
        Schema::create(static::PREFIX . 'countries', function (Blueprint $table) {
            $table->char('country_id', 2)->primary();
            $table->json('data')->nullable();
            $table->boolean('active')->default(1);
            $table->unsignedInteger('order_column')->default(0);
        });

        Schema::create(static::PREFIX . 'shipping_profiles', function (Blueprint $table) {
            $table->char('shipping_profile_id', 36)->primary();
            $table->string('provider_id');
            $table->boolean('requires_address')->default(1);
            $table->json('data')->nullable();
            $table->string('state')->default(\Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileState::online->value);
            $table->unsignedInteger('order_column')->default(0);
        });

        Schema::create(static::PREFIX . 'shipping_profile_countries', function (Blueprint $table) {
            $table->char('shipping_profile_id', 36);
            $table->char('country_id', 2);

            $table->primary(['shipping_profile_id', 'country_id'], 'trader_shipping_profile_id_country_id_primary');
            $table->foreign('shipping_profile_id')->references('shipping_profile_id')->on(static::PREFIX . 'shipping_profiles')->onDelete('cascade');
            $table->foreign('country_id')->references('country_id')->on(static::PREFIX . 'countries')->onDelete('cascade');
        });

        Schema::create(static::PREFIX . 'shipping_profile_tariffs', function (Blueprint $table) {
            $table->char('tariff_id', 36)->primary();
            $table->char('shipping_profile_id', 36);
            $table->integer('rate')->unsigned();
            $table->integer('from')->unsigned();
            $table->integer('to')->unsigned()->nullable();

            $table->index('shipping_profile_id');
            $table->foreign('shipping_profile_id')->references('shipping_profile_id')->on(static::PREFIX . 'shipping_profiles')->onDelete('cascade');
        });

        Schema::create(static::PREFIX . 'payment_methods', function (Blueprint $table) {
            $table->char('payment_method_id', 36)->primary();
            $table->string('provider_id');

            $table->integer('rate')->unsigned();
            $table->json('data')->nullable();
            $table->string('state')->default(\Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodState::online->value);
            $table->unsignedInteger('order_column')->default(0);
        });

        Schema::create(static::PREFIX . 'payment_method_countries', function (Blueprint $table) {
            $table->char('payment_method_id', 36);
            $table->char('country_id', 2);

            $table->primary(['payment_method_id', 'country_id'], 'trader_payment_method_id_country_id_primary');
            $table->foreign('payment_method_id')->references('payment_method_id')->on(static::PREFIX . 'payment_methods')->onDelete('cascade');
            $table->foreign('country_id')->references('country_id')->on(static::PREFIX . 'countries')->onDelete('cascade');
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
            $table->boolean('is_business');
            $table->string('locale');
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create(static::PREFIX . 'customer_password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create(static::PREFIX . 'customer_addresses', function (Blueprint $table) {
            $table->id('address_id');
            $table->char('type');
            $table->char('customer_id', 36)->index();
            $table->string('line_1')->nullable();
            $table->string('line_2')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country_id')->nullable();
            $table->json('data')->nullable();

            $table->foreign('customer_id')->references('customer_id')->on(static::PREFIX . 'customers')->onDelete('cascade');
            $table->foreign('country_id')->references('country_id')->on(static::PREFIX . 'countries');
        });
    }

    private function upOrders()
    {
        Schema::create(static::PREFIX . 'orders', function (Blueprint $table) {
            $table->char('order_id', 36)->primary();
            $table->char('order_ref', 60)->unique(); // For customer / external communication
            $table->string('invoice_ref', 60)->nullable()->unique();
            $table->string('order_state', 32);

            $table->integer('total')->unsigned();
            $table->boolean('includes_vat');
            $table->integer('tax_total')->unsigned();
            $table->integer('subtotal')->unsigned();
            $table->integer('discount_total')->unsigned();
            $table->integer('shipping_cost')->unsigned();
            $table->integer('payment_cost')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('confirmed_at')->nullable(); // TODO: These should be based on the events?
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('data')->nullable();
        });

        Schema::create(static::PREFIX . 'order_lines', function (Blueprint $table) {
            $table->char('order_id', 36)->index();
            $table->char('line_id', 36)->primary();
            $table->char('variant_id', 36)->nullable()->index(); // reference to original/current product
            $table->integer('total')->unsigned();
            $table->integer('discount_total')->unsigned();
            $table->integer('tax_total')->unsigned();
            $table->smallInteger('quantity')->unsigned();
            $table->boolean('reduced_from_stock')->default(0);
            $table->integer('line_price')->unsigned();
            $table->string('tax_rate');
            $table->boolean('includes_vat');
            $table->json('data')->nullable(); // Contains historic product data like name

            $table->foreign('order_id')->references('order_id')->on(static::PREFIX . 'orders')->onDelete('cascade');
            $table->foreign('variant_id')->references('variant_id')->on(static::PREFIX . 'product_variants')->nullOnDelete();
        });

        Schema::create(static::PREFIX . 'order_shoppers', function (Blueprint $table) {
            $table->char('shopper_id', 36)->primary();
            $table->char('order_id', 36)->index();
            $table->char('customer_id', 36)->nullable()->index();
            $table->string('email');
            $table->boolean('is_business');
            $table->string('locale');
            $table->boolean('register_after_checkout');
            $table->json('data')->nullable();

            $table->foreign('order_id')->references('order_id')->on(static::PREFIX . 'orders')->onDelete('cascade');
            $table->foreign('customer_id')->references('customer_id')->on(static::PREFIX . 'customers')->nullOnDelete();
        });

        Schema::create(static::PREFIX . 'order_shipping', function (Blueprint $table) {
            $table->char('shipping_id', 36)->primary();
            $table->char('order_id', 36)->index();
            $table->char('shipping_profile_id', 36)->nullable()->index();
            $table->string('shipping_state', 32);

            $table->integer('cost')->unsigned();
            $table->string('tax_rate');
            $table->boolean('includes_vat');
            $table->json('data')->nullable();

            $table->foreign('order_id')->references('order_id')->on(static::PREFIX . 'orders')->onDelete('cascade');
            $table->foreign('shipping_profile_id')->references('shipping_profile_id')->on(static::PREFIX . 'shipping_profiles')->nullOnDelete();
        });

        Schema::create(static::PREFIX . 'order_payment', function (Blueprint $table) {
            $table->char('payment_id', 36)->primary();
            $table->char('order_id', 36)->index();
            $table->char('payment_method_id', 36)->nullable()->index();
            $table->string('payment_state', 32);

            $table->integer('cost')->unsigned();
            $table->string('tax_rate');
            $table->boolean('includes_vat');
            $table->json('data')->nullable();

            $table->foreign('order_id')->references('order_id')->on(static::PREFIX . 'orders')->onDelete('cascade');
            $table->foreign('payment_method_id')->references('payment_method_id')->on(static::PREFIX . 'payment_methods')->nullOnDelete();
        });

        Schema::create(static::PREFIX . 'order_addresses', function (Blueprint $table) {
            $table->id('address_id');
            $table->char('type');
            $table->char('order_id', 36)->index();
            $table->string('line_1')->nullable();
            $table->string('line_2')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country_id')->index()->nullable();
            $table->json('data')->nullable();

            $table->foreign('order_id')->references('order_id')->on(static::PREFIX . 'orders')->onDelete('cascade');
        });

        Schema::create(static::PREFIX . 'order_events', function (Blueprint $table) {
            $table->char('entry_id', 36)->primary();
            $table->char('order_id', 36)->index();
            $table->string('event'); // transition.confirmed, transition.paid, notification.delay
            $table->dateTime('at');
            $table->json('data')->nullable();

            $table->foreign('order_id')->references('order_id')->on(static::PREFIX . 'orders')->onDelete('cascade');
        });

        Schema::create(static::PREFIX . 'order_discounts', function (Blueprint $table) {
            $table->char('discount_id', 36)->primary();
            $table->char('order_id', 36)->index();
            $table->string('discountable_type', 72);
            $table->char('discountable_id', 36);
            $table->char('promo_id', 36)->nullable()->index(); // Refers to original promo
            $table->char('promo_discount_id', 36)->nullable()->index(); // Refers to original promo discount
            $table->integer('total')->unsigned();
            $table->string('tax_rate');
            $table->boolean('includes_vat');
            $table->json('data')->nullable();

            $table->foreign('order_id')->references('order_id')->on(static::PREFIX . 'orders')->onDelete('cascade');
            $table->foreign('promo_id')->references('promo_id')->on(static::PREFIX . 'promos')->nullOnDelete();
            $table->foreign('promo_discount_id')->references('discount_id')->on(static::PREFIX . 'promo_discounts')->nullOnDelete();
        });

        Schema::create(static::PREFIX . 'order_line_personalisations', function (Blueprint $table) {
            $table->char('line_personalisation_id', 36);
            $table->char('order_id', 36)->index();
            $table->char('line_id', 36)->index();
            $table->string('personalisation_type', 72);
            $table->string('value', 255);
            $table->char('personalisation_id', 36)->nullable();
            $table->json('data')->nullable();

            $table->primary('line_personalisation_id', 'line_personalisation_id_primary');
            $table->foreign('order_id')->references('order_id')->on(static::PREFIX . 'orders')->onDelete('cascade');
            $table->foreign('line_id')->references('line_id')->on(static::PREFIX . 'order_lines')->onDelete('cascade');
            $table->foreign('personalisation_id')->references('personalisation_id')->on(static::PREFIX . 'product_personalisations')->nullOnDelete();
        });
    }

    private function upPromos()
    {
        Schema::create(static::PREFIX . 'promos', function (Blueprint $table) {
            $table->char('promo_id', 36)->primary();
            $table->string('coupon_code')->nullable()->unique();
            $table->boolean('is_combinable')->default(0);
            $table->string('state', 32);
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->json('data')->nullable();
        });

        Schema::create(static::PREFIX . 'promo_discounts', function (Blueprint $table) {
            $table->char('discount_id', 36)->primary();
            $table->char('promo_id', 36)->index();
            $table->string('key'); // class reference
            $table->json('data')->nullable();

            $table->foreign('promo_id')->references('promo_id')->on(static::PREFIX . 'promos')->onDelete('cascade');
        });

        Schema::create(static::PREFIX . 'promo_discount_conditions', function (Blueprint $table) {
            $table->id();
            $table->char('discount_id', 36)->index();
            $table->string('key'); // class reference
            $table->json('data')->nullable();

            $table->foreign('discount_id')->references('discount_id')->on(static::PREFIX . 'promo_discounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(static::PREFIX . 'order_discounts');
        Schema::dropIfExists(static::PREFIX . 'order_events');
        Schema::dropIfExists(static::PREFIX . 'order_payment');
        Schema::dropIfExists(static::PREFIX . 'order_shipping');
        Schema::dropIfExists(static::PREFIX . 'order_addresses');
        Schema::dropIfExists(static::PREFIX . 'order_customer');
        Schema::dropIfExists(static::PREFIX . 'order_lines');
        Schema::dropIfExists(static::PREFIX . 'orders');

        Schema::dropIfExists(static::PREFIX . 'shipping_profiles');
        Schema::dropIfExists(static::PREFIX . 'shipping_profile_tariffs');
        Schema::dropIfExists(static::PREFIX . 'payment_methods');

        Schema::dropIfExists(static::PREFIX . 'customers');

        Schema::dropIfExists(static::PREFIX . 'taxa_products');
        Schema::dropIfExists(static::PREFIX . 'taxa');
        Schema::dropIfExists(static::PREFIX . 'product_variants');
        Schema::dropIfExists(static::PREFIX . 'products');
        Schema::dropIfExists(static::PREFIX . 'promos');
        Schema::dropIfExists(static::PREFIX . 'promo_discounts');
        Schema::dropIfExists(static::PREFIX . 'promo_discount_conditions');
        Schema::dropIfExists(static::PREFIX . 'countries');
    }
};
