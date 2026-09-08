<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Migrations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Money\Money;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

final class TaxModesMigrationTest extends TestCase
{
    public function test_it_applies_legacy_defaults_and_discount_backfill_idempotently(): void
    {
        $orderContext = OrderContext::mysql();
        $paymentMethod = $orderContext->createPaymentMethod('legacy-payment');
        $profile = $orderContext->dontPersist()->createShippingProfile('legacy-profile');
        $profile->addTariff(Tariff::create(
            $orderContext->repos()->shippingProfileRepository()->nextTariffReference(),
            $profile->shippingProfileId,
            Money::EUR(700),
            Money::EUR(0),
            null,
            TaxMode::Inclusive,
        ));
        $orderContext->repos()->shippingProfileRepository()->save($profile);
        $orderContext->persist();
        $order = $orderContext->createDefaultOrder('legacy-order');
        $orderContext->addDiscountToOrder($order, $orderContext->createOrderDiscount('legacy-order', 'legacy-discount', [
            'total_excl' => '10',
            'total_incl' => '12',
            'tax_mode' => TaxMode::Inclusive->value,
            'promo_id' => null,
            'promo_discount_id' => null,
        ]));

        $migration = require dirname(__DIR__, 3).'/src/Infrastructure/Laravel/database/migrations/2026_09_04_000000_add_tax_modes_to_prices.php';
        $migration->down();
        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasColumn('trader_shipping_profile_tariffs', 'tax_mode'));
        $this->assertTrue(Schema::hasColumn('trader_payment_methods', 'tax_mode'));
        $this->assertTrue(Schema::hasColumn('trader_order_discounts', 'tax_mode'));
        $this->assertTrue(Schema::hasColumn('trader_orders', 'pricing_fingerprint'));
        $this->assertSame(TaxMode::Exclusive->value, DB::table('trader_payment_methods')->where('payment_method_id', $paymentMethod->paymentMethodId->get())->value('tax_mode'));
        $this->assertSame(TaxMode::Exclusive->value, DB::table('trader_shipping_profile_tariffs')->where('shipping_profile_id', $profile->shippingProfileId->get())->value('tax_mode'));
        $this->assertSame(TaxMode::Inclusive->value, DB::table('trader_order_discounts')->where('order_id', $order->orderId->get())->value('tax_mode'));
        $this->assertSame(1, DB::table('trader_shipping_profiles')->where('shipping_profile_id', $profile->shippingProfileId->get())->count());
    }
}
