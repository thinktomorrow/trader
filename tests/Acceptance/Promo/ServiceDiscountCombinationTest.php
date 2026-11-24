<?php

declare(strict_types=1);

namespace Tests\Acceptance\Promo;

use Money\Money;
use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Cart\PaymentMethod\UpdatePaymentMethodOnOrder;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartDiscount;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderPricingSnapshot;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustPayment;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustShipping;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\UpdateShippingProfileOnOrder;
use Thinktomorrow\Trader\Application\Promo\ApplicableConditions\ApplicableConditionFactory;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\ApplicableDiscountFactory;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\BaseApplicableDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\OrderSubtotalDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\PaymentDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\ProductDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\ShippingDiscount;
use Thinktomorrow\Trader\Application\Promo\Preview\FindCartPromoIncentives;
use Thinktomorrow\Trader\Application\Promo\Preview\FindPromoSuggestionsForProduct;
use Thinktomorrow\Trader\Application\Promo\SelectBestPromoCombination;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultServicePrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\AppliedDiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodProviderId;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\BaseDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\Discount as PromoDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProviderId;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

final class ServiceDiscountCombinationTest extends TestCase
{
    public function test_cart_incentives_include_promos_for_selected_shipping_and_payment(): void
    {
        $shippingPromo = $this->promo('shipping', DiscountableType::shipping, [], 10);
        $shippingPromo->addData(['show_on_cart' => true]);
        $paymentPromo = $this->promo('payment', DiscountableType::payment, [], 10);
        $paymentPromo->addData(['show_on_cart' => true]);
        $orderPromo = $this->promo('order', DiscountableType::order, [], 10);
        $orderPromo->addData(['show_on_cart' => true]);

        $repository = $this->createMock(PromoRepository::class);
        $repository->method('getAvailablePromos')->willReturn([$shippingPromo, $paymentPromo, $orderPromo]);
        $shipping = $this->createMock(CartShipping::class);
        $payment = $this->createMock(CartPayment::class);
        $cart = $this->createMock(Cart::class);
        $cart->method('getLines')->willReturn([]);
        $cart->method('getShipping')->willReturn($shipping);
        $cart->method('getPayment')->willReturn($payment);

        $query = new FindCartPromoIncentives(new FindPromoSuggestionsForProduct(
            $repository,
            new ApplicableDiscountFactory([ServiceTestDiscount::class], new ApplicableConditionFactory([])),
        ));

        $this->assertSame(['shipping', 'payment'], $query->get($cart, 'nl')->pluck('promoId')->all());

        $appliedDiscount = $this->createMock(CartDiscount::class);
        $appliedDiscount->method('getData')->with('promo_id')->willReturn('shipping');
        $cartWithAppliedShipping = $this->createMock(Cart::class);
        $cartWithAppliedShipping->method('getLines')->willReturn([]);
        $cartWithAppliedShipping->method('getShipping')->willReturn($shipping);
        $cartWithAppliedShipping->method('getPayment')->willReturn($payment);
        $cartWithAppliedShipping->method('getAllDiscounts')->willReturn([$appliedDiscount]);

        $this->assertSame(['payment'], $query->get($cartWithAppliedShipping, 'nl')->pluck('promoId')->all());
    }

    public function test_no_candidates_skip_trial_but_still_refresh_services(): void
    {
        $order = $this->orderContext->createDefaultOrder();
        $pricing = $this->createMock(AdjustOrderPricingSnapshot::class);
        $pricing->expects($this->never())->method('calculate');
        $shipping = $this->createMock(UpdateShippingProfileOnOrder::class);
        $shipping->expects($this->never())->method('findForRefresh');
        $shipping->expects($this->once())->method('refresh');
        $payment = $this->createMock(UpdatePaymentMethodOnOrder::class);
        $payment->expects($this->never())->method('findForRefresh');
        $payment->expects($this->once())->method('refresh');

        $this->selector($shipping, $payment, $pricing)->apply($order, []);
    }

    public function test_selected_service_profiles_are_loaded_once_for_all_trials_and_final_application(): void
    {
        $order = $this->orderContext->createDefaultOrder();
        $shippingProfile = ShippingProfile::create($order->getShippings()[0]->getShippingProfileId(), ShippingProviderId::fromString('postnl'), false);
        $paymentMethod = PaymentMethod::create($order->getPayments()[0]->getPaymentMethodId(), PaymentMethodProviderId::fromString('mollie'), Money::EUR(10));

        $shipping = $this->createMock(UpdateShippingProfileOnOrder::class);
        $shipping->expects($this->once())->method('findForRefresh')->willReturn($shippingProfile);
        $shipping->expects($this->atLeast(2))->method('refreshWithProfile')->with($this->isInstanceOf(Order::class), $shippingProfile);
        $shipping->expects($this->never())->method('refresh');

        $payment = $this->createMock(UpdatePaymentMethodOnOrder::class);
        $payment->expects($this->once())->method('findForRefresh')->willReturn($paymentMethod);
        $payment->expects($this->atLeast(2))->method('refreshWithMethod')->with($this->isInstanceOf(Order::class), $paymentMethod);
        $payment->expects($this->never())->method('refresh');

        $this->selector($shipping, $payment)->apply($order, [
            $this->promo('shipping', DiscountableType::shipping, ['payment'], 10),
            $this->promo('payment', DiscountableType::payment, ['shipping'], 20),
        ]);

        $this->assertCount(1, $order->getShippings()[0]->getDiscounts());
        $this->assertCount(1, $order->getPayments()[0]->getDiscounts());
    }

    public function test_shipping_and_payment_promos_combine_when_each_allows_the_other_class(): void
    {
        $order = $this->orderContext->createDefaultOrder();
        $shippingPromo = $this->promo('shipping', DiscountableType::shipping, ['payment'], 10);
        $paymentPromo = $this->promo('payment', DiscountableType::payment, ['shipping'], 20);
        $selector = $this->selector();

        $selector->apply($order, [$shippingPromo, $paymentPromo]);

        $this->assertSame('shipping', $order->getShippings()[0]->getDiscounts()[0]->promoId->get());
        $this->assertSame('payment', $order->getPayments()[0]->getDiscounts()[0]->promoId->get());
        $this->assertEquals(Money::EUR(10), $order->getShippings()[0]->getDiscountPrice()->getExcludingVat());
        $this->assertEquals(Money::EUR(20), $order->getPayments()[0]->getDiscountPrice()->getExcludingVat());
    }

    public function test_two_compatible_shipping_promos_apply_to_the_same_shipping_cost(): void
    {
        $order = $this->orderContext->createDefaultOrder();
        $selector = $this->selector();

        $selector->apply($order, [
            $this->promo('less', DiscountableType::shipping, ['shipping'], 10),
            $this->promo('more', DiscountableType::shipping, ['shipping'], 20),
        ]);

        $this->assertCount(2, $order->getShippings()[0]->getDiscounts());
        $this->assertEquals(Money::EUR(30), $order->getShippings()[0]->getDiscountPrice()->getExcludingVat());
    }

    public function test_service_discounts_stop_at_the_remaining_cost(): void
    {
        $order = $this->orderContext->createDefaultOrder();

        $this->selector()->apply($order, [
            $this->promo('first', DiscountableType::shipping, ['shipping'], 30),
            $this->promo('second', DiscountableType::shipping, ['shipping'], 40),
        ]);

        $shipping = $order->getShippings()[0];
        $this->assertCount(2, $shipping->getDiscounts());
        $this->assertEquals(Money::EUR(50), $shipping->getDiscountPrice()->getExcludingVat());
        $this->assertEquals(Money::EUR(0), $shipping->getShippingCostTotal()->getExcludingVat());
    }

    public function test_two_compatible_payment_promos_stack_up_to_the_payment_cost(): void
    {
        $order = $this->orderContext->createDefaultOrder();

        $this->selector()->apply($order, [
            $this->promo('first', DiscountableType::payment, ['payment'], 30),
            $this->promo('second', DiscountableType::payment, ['payment'], 40),
        ]);

        $payment = $order->getPayments()[0];
        $this->assertCount(2, $payment->getDiscounts());
        $this->assertEquals(Money::EUR(50), $payment->getDiscountPrice()->getExcludingVat());
        $this->assertEquals(Money::EUR(0), $payment->getPaymentCostTotal()->getExcludingVat());
    }

    public function test_competing_product_promos_choose_the_best_discount_on_each_line(): void
    {
        $order = $this->orderContext->createDefaultOrder();

        $this->selector()->apply($order, [
            $this->linePromo('pickup', 10),
            $this->linePromo('sale', 30, 'line-bbb'),
        ]);

        $this->assertSame(['pickup'], array_map(fn ($discount) => $discount->promoId->get(), $order->getLines()[0]->getDiscounts()));
        $this->assertSame(['sale'], array_map(fn ($discount) => $discount->promoId->get(), $order->getLines()[1]->getDiscounts()));
    }

    public function test_a_mixed_promo_keeps_its_order_discount_when_its_line_discount_loses_on_one_line(): void
    {
        $order = $this->orderContext->createDefaultOrder();
        $this->selector()->apply($order, [
            $this->linePromo('mixed', 10, null, ['line', 'order'], true),
            $this->linePromo('sale', 30, 'line-bbb', ['line', 'order']),
        ]);

        $this->assertSame(['mixed'], array_map(fn ($discount) => $discount->promoId->get(), $order->getLines()[0]->getDiscounts()));
        $this->assertSame(['sale'], array_map(fn ($discount) => $discount->promoId->get(), $order->getLines()[1]->getDiscounts()));
        $this->assertSame(['mixed'], array_map(fn ($discount) => $discount->promoId->get(), $order->getDiscounts()));
    }

    public function test_custom_unconditional_order_discounts_can_combine(): void
    {
        $order = $this->orderContext->createDefaultOrder();
        $promos = [];

        foreach (range(1, 14) as $number) {
            $id = 'order-'.$number;
            $discount = OrderTestPromoDiscount::fromMappedData(
                ['discount_id' => $id.'-discount', 'discountable_type' => 'order', 'data' => '[]'],
                ['promo_id' => $id, 'coupon_code' => null, 'data' => '[]'],
                [Condition::class => []],
            );
            $promos[] = Promo::fromMappedData(
                ['promo_id' => $id, 'state' => 'online', 'is_system_promo' => false, 'coupon_code' => null, 'combines_with' => '["order"]', 'multiple_product_discounts_per_line' => false, 'data' => '[]', 'start_at' => null, 'end_at' => null],
                [PromoDiscount::class => [$discount]],
            );
        }

        $this->selector()->apply($order, $promos);

        $this->assertCount(14, $order->getDiscounts());
        $this->assertEquals(Money::EUR(14), $order->getDiscountTotalExcl());
    }

    public function test_including_vat_shipping_discounts_cannot_exceed_either_cost_amount(): void
    {
        $order = $this->orderContext->createDefaultOrder();
        $shipping = $order->getShippings()[0];
        $shipping->updateCost(DefaultServicePrice::fromIncludingVat(Money::EUR(60), Money::EUR(50)));

        foreach (['first', 'second'] as $id) {
            $discount = ServiceTestDiscount::fromMappedData(
                ['discount_id' => $id.'-discount', 'discountable_type' => 'shipping', 'data' => json_encode(['amount' => 30, 'inclusive' => true])],
                ['promo_id' => $id, 'coupon_code' => null, 'data' => '[]'],
                [],
                $this->catalogContext->apps()->vatAllocator(),
            );
            $discount->apply($order, $shipping, AppliedDiscountId::fromString($id));
        }

        $this->assertCount(2, $shipping->getDiscounts());
        $this->assertEquals(Money::EUR(50), $shipping->getDiscountPrice()->getExcludingVat());
        $this->assertEquals(Money::EUR(60), $shipping->getDiscountPrice()->getAuthoritativeAmount());
        $this->assertEquals(Money::EUR(0), $shipping->getShippingCostTotal()->getAuthoritativeAmount());
    }

    private function selector(?UpdateShippingProfileOnOrder $shipping = null, ?UpdatePaymentMethodOnOrder $payment = null, ?AdjustOrderPricingSnapshot $pricing = null): SelectBestPromoCombination
    {
        return new SelectBestPromoCombination(
            $this->orderContext->repos()->orderRepository(),
            new TestTraderConfig,
            $pricing ?? new AdjustOrderPricingSnapshot($this->catalogContext->apps()->vatAllocator()),
            new AdjustShipping($shipping ?? $this->createStub(UpdateShippingProfileOnOrder::class)),
            new AdjustPayment($payment ?? $this->createStub(UpdatePaymentMethodOnOrder::class)),
            new ApplicableDiscountFactory([ServiceTestDiscount::class, OrderTestDiscount::class, LineTestDiscount::class], new ApplicableConditionFactory([]), $this->catalogContext->apps()->vatAllocator()),
        );
    }

    /** @param list<string> $combinesWith */
    private function linePromo(string $id, int $amount, ?string $lineId = null, array $combinesWith = ['line'], bool $withOrderDiscount = false): Promo
    {
        $discount = LineTestPromoDiscount::fromMappedData(
            ['discount_id' => $id.'-discount', 'discountable_type' => 'line', 'data' => json_encode(['amount' => $amount, 'line_id' => $lineId])],
            ['promo_id' => $id, 'coupon_code' => null, 'data' => '[]'],
            [Condition::class => []],
        );

        $discounts = [$discount];

        if ($withOrderDiscount) {
            $discounts[] = OrderTestPromoDiscount::fromMappedData(
                ['discount_id' => $id.'-order', 'discountable_type' => 'order', 'data' => '[]'],
                ['promo_id' => $id, 'coupon_code' => null, 'data' => '[]'],
                [Condition::class => []],
            );
        }

        return Promo::fromMappedData(
            ['promo_id' => $id, 'state' => 'online', 'is_system_promo' => false, 'coupon_code' => null, 'combines_with' => json_encode($combinesWith), 'multiple_product_discounts_per_line' => false, 'data' => '[]', 'start_at' => null, 'end_at' => null],
            [PromoDiscount::class => $discounts],
        );
    }

    /** @param list<string> $combinesWith */
    private function promo(string $id, DiscountableType $type, array $combinesWith, int $amount): Promo
    {
        $discount = ServiceTestPromoDiscount::fromMappedData(
            ['discount_id' => $id.'-discount', 'discountable_type' => $type->value, 'data' => json_encode(['amount' => $amount, 'inclusive' => false])],
            ['promo_id' => $id, 'coupon_code' => null, 'data' => '[]'],
            [Condition::class => []],
        );

        return Promo::fromMappedData(
            ['promo_id' => $id, 'state' => 'online', 'is_system_promo' => false, 'coupon_code' => null, 'combines_with' => json_encode($combinesWith), 'multiple_product_discounts_per_line' => false, 'data' => '[]', 'start_at' => null, 'end_at' => null],
            [PromoDiscount::class => [$discount]],
        );
    }
}

final class ServiceTestPromoDiscount extends BaseDiscount implements PromoDiscount
{
    public static function getMapKey(): string
    {
        return 'test-service-discount';
    }
}

final class LineTestPromoDiscount extends BaseDiscount implements PromoDiscount
{
    public static function getMapKey(): string
    {
        return 'test-line-discount';
    }
}

final class LineTestDiscount extends BaseApplicableDiscount implements ProductDiscount
{
    private int $amount;

    private ?string $lineId;

    public static function getMapKey(): string
    {
        return LineTestPromoDiscount::getMapKey();
    }

    public function isApplicable(Order $order, DiscountableItem $line): bool
    {
        return $line instanceof Line
            && ($this->lineId === null || str_ends_with($line->lineId->get(), ':'.$this->lineId))
            && parent::isApplicable($order, $line);
    }

    public function getDiscountPrice(Order $order, DiscountableItem $line): ItemDiscountPrice
    {
        return DefaultItemDiscountPrice::fromExcludingVat(Money::EUR($this->amount), $line->getUnitPrice()->getVatPercentage());
    }

    public function setCalculationTaxMode(TaxMode $taxMode): void {}

    public static function fromMappedData(array $state, array $aggregateState, array $conditions, VatAllocator $vatAllocator): static
    {
        $discount = parent::fromMappedData($state, $aggregateState, $conditions, $vatAllocator);
        $discount->amount = json_decode($state['data'], true)['amount'];
        $discount->lineId = json_decode($state['data'], true)['line_id'];

        return $discount;
    }
}

final class OrderTestPromoDiscount extends BaseDiscount implements PromoDiscount
{
    public static function getMapKey(): string
    {
        return 'test-order-discount';
    }
}

final class OrderTestDiscount extends BaseApplicableDiscount implements OrderSubtotalDiscount
{
    public static function getMapKey(): string
    {
        return OrderTestPromoDiscount::getMapKey();
    }

    public function isApplicable(Order $order, DiscountableItem $target): bool
    {
        return $target instanceof Order && parent::isApplicable($order, $target);
    }

    public function getDiscountPrice(Order $order, DiscountableItem $target): DiscountPrice
    {
        return DefaultDiscountPrice::fromExcludingVat(Money::EUR(1));
    }
}

final class ServiceTestDiscount extends BaseApplicableDiscount implements PaymentDiscount, ShippingDiscount
{
    private int $amount;

    private bool $inclusive;

    public static function getMapKey(): string
    {
        return 'test-service-discount';
    }

    public function isApplicable(Order $order, DiscountableItem $discountable): bool
    {
        return match ($this->getDiscountableType()) {
            DiscountableType::shipping => $discountable instanceof Shipping,
            DiscountableType::payment => $discountable instanceof Payment,
            default => false,
        };
    }

    public function getDiscountPrice(Order $order, DiscountableItem $discountable): DiscountPrice
    {
        return $this->inclusive
            ? DefaultDiscountPrice::fromIncludingVat(Money::EUR($this->amount + 10), Money::EUR($this->amount))
            : DefaultDiscountPrice::fromExcludingVat(Money::EUR($this->amount));
    }

    public static function fromMappedData(array $state, array $aggregateState, array $conditions, VatAllocator $vatAllocator): static
    {
        $discount = parent::fromMappedData($state, $aggregateState, $conditions, $vatAllocator);
        $discount->amount = json_decode($state['data'], true)['amount'];
        $discount->inclusive = json_decode($state['data'], true)['inclusive'];

        return $discount;
    }
}
