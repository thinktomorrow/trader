<?php

declare(strict_types=1);

namespace Tests\Unit\Promo;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Promo\ApplicableConditions\ApplicableConditionFactory;
use Thinktomorrow\Trader\Application\Promo\ApplicableConditions\MinimumAmountCondition;
use Thinktomorrow\Trader\Application\Promo\ApplicableConditions\MinimumLinesQuantityCondition;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\ApplicableDiscountFactory;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\FixedAmountOrderSubtotalDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\PercentageOffOrderSubtotalDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\SalePriceProductDiscount;
use Thinktomorrow\Trader\Application\Promo\Preview\FindPromoSuggestionsForProduct;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewProduct;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewStatus;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Promo\CombinationPolicy;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumAmount;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PercentageOffDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\SalePriceSystemDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;

final class FindPromoSuggestionsForProductTest extends TestCase
{
    public function test_it_separates_applicable_and_conditional_promos_and_only_publishes_opted_in_coupons(): void
    {
        $automatic = $this->promo('automatic', null, 1000, [
            'show_on_pdp' => true,
            'pdp_description' => ['nl' => 'Nu beschikbaar'],
        ]);
        $coupon = $this->promo('coupon', 'SAVE', 1000, [
            'show_on_pdp' => true,
            'pdp_description' => ['nl' => 'Gebruik de code'],
        ]);
        $hidden = $this->promo('hidden', 'PRIVATE', 1000, []);
        $minimum = $this->promo('minimum', null, 20000, ['show_on_pdp' => true]);

        $repository = $this->createMock(PromoRepository::class);
        $repository->method('getAvailablePromos')->willReturn([$automatic, $coupon, $hidden, $minimum]);
        $product = $this->createMock(ProductDetail::class);
        $product->method('isAvailable')->willReturn(true);
        $product->method('getSalePrice')->willReturn(VariantSalePrice::fromScalars('10000', '21', false));
        $product->method('getUnitPrice')->willReturn(VariantUnitPrice::fromScalars('10000', '21', false));

        $query = new FindPromoSuggestionsForProduct($repository, $this->discountFactory());
        $result = $query->get($product, 'nl');

        $this->assertSame(['automatic'], $result->applicablePromos->pluck('promoId')->all());
        $this->assertSame(['coupon', 'minimum'], $result->conditionalPromos->pluck('promoId')->all());
        $this->assertSame('Nu beschikbaar', $result->applicablePromos->first()->pdpDescription);
        $this->assertSame('automatic-discount', $result->applicablePromos->first()->discountId);

        $cart = $this->createMock(Cart::class);
        $cart->method('getEnteredCoupon')->willReturn('SAVE');
        $cart->method('getSubtotalExcl')->willReturn(Money::EUR(0));
        $cart->method('getQuantity')->willReturn(0);
        $this->assertSame(['automatic', 'coupon'], $query->get($product, 'nl', cart: $cart)->applicablePromos->pluck('promoId')->all());
    }

    public function test_cart_incentive_is_independent_of_pdp_visibility_and_coupon_application(): void
    {
        $promo = $this->promo('coupon', 'SAVE', 20000, [
            'show_on_cart' => true,
            'cart_description' => ['nl' => 'Voeg nog iets toe en gebruik SAVE'],
        ]);
        $repository = $this->createMock(PromoRepository::class);
        $repository->method('getAvailablePromos')->willReturn([$promo]);
        $product = $this->createMock(ProductDetail::class);
        $product->method('isAvailable')->willReturn(true);
        $product->method('getSalePrice')->willReturn(VariantSalePrice::fromScalars('10000', '21', false));
        $product->method('getUnitPrice')->willReturn(VariantUnitPrice::fromScalars('10000', '21', false));
        $cart = $this->createMock(Cart::class);
        $cart->method('getSubtotalExcl')->willReturn(Money::EUR(10000));
        $cart->method('getQuantity')->willReturn(1);

        $query = new FindPromoSuggestionsForProduct($repository, $this->discountFactory());

        $this->assertSame('Voeg nog iets toe en gebruik SAVE', $query->cartIncentivesFor(
            PromoPreviewProduct::fromProductDetail($product, 0), 'nl', $cart,
        )->first()->cartDescription);
        $this->assertCount(0, $query->get($product, 'nl')->conditionalPromos);
    }

    public function test_minimum_quantity_is_conditional_until_the_preview_reaches_the_threshold(): void
    {
        $promo = $this->promo('quantity', null, 0, [
            'show_on_pdp' => true,
        ]);
        $promo->getDiscounts()[0]->updateConditions([
            MinimumLinesQuantity::fromMappedData(['data' => '{"minimum_quantity":2}'], []),
        ]);

        $repository = $this->createMock(PromoRepository::class);
        $repository->method('getAvailablePromos')->willReturn([$promo]);
        $product = $this->createMock(ProductDetail::class);
        $product->method('isAvailable')->willReturn(true);
        $product->method('getSalePrice')->willReturn(VariantSalePrice::fromScalars('10000', '21', false));
        $product->method('getUnitPrice')->willReturn(VariantUnitPrice::fromScalars('10000', '21', false));

        $query = new FindPromoSuggestionsForProduct($repository, $this->discountFactory());

        $this->assertSame(['quantity'], $query->get($product, 'nl')->conditionalPromos->pluck('promoId')->all());
        $this->assertSame(['quantity'], $query->get($product, 'nl', 2)->applicablePromos->pluck('promoId')->all());
    }

    public function test_order_promo_is_only_a_pdp_incentive_when_the_product_contributes_to_the_minimum(): void
    {
        $promo = $this->promo('minimum', null, 20000, [
            'show_on_pdp' => true,
        ]);
        $repository = $this->createMock(PromoRepository::class);
        $repository->method('getAvailablePromos')->willReturn([$promo]);
        $product = $this->createMock(ProductDetail::class);
        $product->method('isAvailable')->willReturn(true);
        $product->method('getSalePrice')->willReturn(VariantSalePrice::fromScalars('10000', '21', false));
        $product->method('getUnitPrice')->willReturn(VariantUnitPrice::fromScalars('10000', '21', false));
        $query = new FindPromoSuggestionsForProduct($repository, $this->discountFactory());

        $alreadyEligibleCart = $this->createMock(Cart::class);
        $alreadyEligibleCart->method('getSubtotalExcl')->willReturn(Money::EUR(20000));
        $alreadyEligibleCart->method('getQuantity')->willReturn(2);
        $preview = PromoPreviewProduct::fromProductDetail($product, 1);
        $this->assertSame(PromoPreviewStatus::AlreadyEligible, $this->discountFactory()
            ->forDiscount($promo, $promo->getDiscounts()[0])
            ->promoPreviewStatus($preview, $alreadyEligibleCart));
        $this->assertTrue($query->get($product, 'nl', cart: $alreadyEligibleCart)->applicablePromos->isEmpty());
        $this->assertTrue($query->get($product, 'nl', cart: $alreadyEligibleCart)->conditionalPromos->isEmpty());

        $thresholdCart = $this->createMock(Cart::class);
        $thresholdCart->method('getSubtotalExcl')->willReturn(Money::EUR(10000));
        $thresholdCart->method('getQuantity')->willReturn(1);
        $this->assertSame(['minimum'], $query->get($product, 'nl', cart: $thresholdCart)->applicablePromos->pluck('promoId')->all());

        $pendingCart = $this->createMock(Cart::class);
        $pendingCart->method('getSubtotalExcl')->willReturn(Money::EUR(5000));
        $pendingCart->method('getQuantity')->willReturn(1);
        $this->assertSame(['minimum'], $query->get($product, 'nl', cart: $pendingCart)->conditionalPromos->pluck('promoId')->all());
    }

    public function test_coupon_only_order_promo_is_not_a_pdp_incentive(): void
    {
        $promo = $this->promo('coupon', 'SAVE', 0, [
            'show_on_pdp' => true,
            'show_on_cart' => true,
        ]);
        $promo->getDiscounts()[0]->updateConditions([]);
        $repository = $this->createMock(PromoRepository::class);
        $repository->method('getAvailablePromos')->willReturn([$promo]);
        $product = $this->createMock(ProductDetail::class);
        $product->method('isAvailable')->willReturn(true);
        $product->method('getSalePrice')->willReturn(VariantSalePrice::fromScalars('10000', '21', false));
        $product->method('getUnitPrice')->willReturn(VariantUnitPrice::fromScalars('10000', '21', false));
        $query = new FindPromoSuggestionsForProduct($repository, $this->discountFactory());

        $suggestions = $query->get($product, 'nl');

        $this->assertTrue($suggestions->applicablePromos->isEmpty());
        $this->assertTrue($suggestions->conditionalPromos->isEmpty());

        $cart = $this->createMock(Cart::class);
        $cart->method('getSubtotalExcl')->willReturn(Money::EUR(10000));
        $cart->method('getQuantity')->willReturn(1);
        $this->assertSame(['coupon'], $query->cartIncentivesFor(
            PromoPreviewProduct::fromProductDetail($product, 0), 'nl', $cart,
        )->pluck('promoId')->all());
    }

    public function test_an_already_met_condition_does_not_hide_progress_toward_another_condition(): void
    {
        $promo = $this->promo('combined', null, 1000, ['show_on_pdp' => true]);
        $promo->getDiscounts()[0]->updateConditions([
            MinimumAmount::fromMappedData(['data' => '{"amount":1000}'], []),
            MinimumLinesQuantity::fromMappedData(['data' => '{"minimum_quantity":3}'], []),
        ]);
        $repository = $this->createMock(PromoRepository::class);
        $repository->method('getAvailablePromos')->willReturn([$promo]);
        $product = $this->createMock(ProductDetail::class);
        $product->method('isAvailable')->willReturn(true);
        $product->method('getSalePrice')->willReturn(VariantSalePrice::fromScalars('10000', '21', false));
        $product->method('getUnitPrice')->willReturn(VariantUnitPrice::fromScalars('10000', '21', false));
        $cart = $this->createMock(Cart::class);
        $cart->method('getSubtotalExcl')->willReturn(Money::EUR(5000));
        $cart->method('getQuantity')->willReturn(1);

        $query = new FindPromoSuggestionsForProduct($repository, $this->discountFactory());

        $this->assertSame(['combined'], $query->get($product, 'nl', cart: $cart)->conditionalPromos->pluck('promoId')->all());
    }

    public function test_free_product_does_not_advance_an_order_amount_minimum(): void
    {
        $promo = $this->promo('minimum', null, 1000, ['show_on_pdp' => true]);
        $repository = $this->createMock(PromoRepository::class);
        $repository->method('getAvailablePromos')->willReturn([$promo]);
        $product = $this->createMock(ProductDetail::class);
        $product->method('isAvailable')->willReturn(true);
        $product->method('getSalePrice')->willReturn(VariantSalePrice::fromScalars('0', '21', false));
        $product->method('getUnitPrice')->willReturn(VariantUnitPrice::fromScalars('0', '21', false));

        $this->assertTrue((new FindPromoSuggestionsForProduct($repository, $this->discountFactory()))
            ->get($product, 'nl')->conditionalPromos->isEmpty());
    }

    public function test_sale_price_discount_is_only_relevant_for_sale_products_and_respects_its_coupon(): void
    {
        $promo = Promo::create(PromoId::fromString('sale'), 'SALE', null, null, new CombinationPolicy([]));
        $promo->addData(['show_on_pdp' => true]);
        $promo->addPromoDiscount(SalePriceSystemDiscount::fromMappedData(
            ['discount_id' => 'sale-discount', 'data' => '{}'],
            ['promo_id' => 'sale'],
            [Condition::class => []],
        ));

        $repository = $this->createMock(PromoRepository::class);
        $repository->method('getAvailablePromos')->willReturn([$promo]);
        $product = $this->createMock(ProductDetail::class);
        $product->method('isAvailable')->willReturn(true);
        $product->method('getSalePrice')->willReturn(VariantSalePrice::fromScalars('8000', '21', false));
        $product->method('getUnitPrice')->willReturn(VariantUnitPrice::fromScalars('10000', '21', false));

        $query = new FindPromoSuggestionsForProduct($repository, $this->discountFactory());
        $this->assertSame(['sale'], $query->get($product, 'nl')->conditionalPromos->pluck('promoId')->all());

        $cart = $this->createMock(Cart::class);
        $cart->method('getEnteredCoupon')->willReturn('SALE');
        $cart->method('getSubtotalExcl')->willReturn(Money::EUR(0));
        $cart->method('getQuantity')->willReturn(0);
        $this->assertSame(['sale'], $query->get($product, 'nl', cart: $cart)->applicablePromos->pluck('promoId')->all());
    }

    /** @param array<string, mixed> $data */
    private function promo(string $id, ?string $couponCode, int $minimum, array $data): Promo
    {
        $promo = Promo::create(PromoId::fromString($id), $couponCode, null, null, new CombinationPolicy([]));
        $promo->addData($data);
        $condition = MinimumAmount::fromMappedData(['data' => json_encode(['amount' => $minimum])], []);
        $discount = PercentageOffDiscount::fromMappedData(
            ['discount_id' => $id.'-discount', 'data' => json_encode(['percentage' => '10'])],
            ['promo_id' => $id],
            [Condition::class => [$condition]],
        );
        $promo->addPromoDiscount($discount);

        return $promo;
    }

    private function discountFactory(): ApplicableDiscountFactory
    {
        return new ApplicableDiscountFactory(
            [SalePriceProductDiscount::class, PercentageOffOrderSubtotalDiscount::class, FixedAmountOrderSubtotalDiscount::class],
            new ApplicableConditionFactory([MinimumAmountCondition::class, MinimumLinesQuantityCondition::class]),
        );
    }
}
