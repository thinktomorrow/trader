<?php

declare(strict_types=1);

namespace Tests\Unit\Promo;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartDiscount;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Application\Promo\ApplicableConditions\ApplicableConditionFactory;
use Thinktomorrow\Trader\Application\Promo\ApplicableConditions\MinimumAmountCondition;
use Thinktomorrow\Trader\Application\Promo\ApplicableConditions\MinimumLinesQuantityCondition;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\ApplicableDiscountFactory;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\FixedAmountOrderSubtotalDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\PercentageOffOrderSubtotalDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\SalePriceProductDiscount;
use Thinktomorrow\Trader\Application\Promo\Preview\FindCartPromoIncentives;
use Thinktomorrow\Trader\Application\Promo\Preview\FindPromoSuggestionsForProduct;
use Thinktomorrow\Trader\Domain\Model\Order\Line\PurchasableReference;
use Thinktomorrow\Trader\Domain\Model\Promo\CombinationPolicy;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PercentageOffDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;

final class FindCartPromoIncentivesTest extends TestCase
{
    public function test_it_shows_one_cart_incentive_when_multiple_lines_qualify_for_the_same_promo(): void
    {
        $promo = Promo::create(PromoId::fromString('cart-promo'), 'SAVE', null, null, new CombinationPolicy([]));
        $promo->addData(['show_on_cart' => true, 'cart_description' => ['nl' => 'Gebruik SAVE']]);
        $promo->addPromoDiscount(PercentageOffDiscount::fromMappedData(
            ['discount_id' => 'cart-discount', 'data' => '{"percentage":"10"}'],
            ['promo_id' => 'cart-promo'],
            [Condition::class => []],
        ));

        $promos = $this->createMock(PromoRepository::class);
        $promos->method('getAvailablePromos')->willReturn([$promo]);
        $line = $this->createMock(CartLine::class);
        $line->method('getPurchasableReference')->willReturn(new PurchasableReference('variant', 'variant-1'));
        $line->method('getUnitPriceExcl')->willReturn(Money::EUR(10000));
        $line->method('getData')->willReturnCallback(fn (?string $key = null, $default = null) => [
            'product_id' => 'product-1',
            'sale_price_excl' => '10000',
            'taxa' => [['taxon_id' => 'taxon-1']],
        ][$key] ?? $default);
        $cart = $this->createMock(Cart::class);
        $cart->method('getLines')->willReturn([$line, $line]);
        $cart->method('getSubtotalExcl')->willReturn(Money::EUR(20000));
        $cart->method('getQuantity')->willReturn(2);

        $query = new FindCartPromoIncentives(new FindPromoSuggestionsForProduct($promos, $this->discountFactory()));

        $this->assertSame(['cart-promo'], $query->get($cart, 'nl')->pluck('promoId')->all());
        $this->assertSame('Gebruik SAVE', $query->get($cart, 'nl')->first()->cartDescription);
    }

    public function test_it_hides_an_incentive_once_its_promo_is_applied_to_a_line(): void
    {
        $promo = Promo::create(PromoId::fromString('cart-promo'), 'SAVE', null, null, new CombinationPolicy([]));
        $promo->addData(['show_on_cart' => true, 'cart_description' => ['nl' => 'Gebruik SAVE']]);
        $promo->addPromoDiscount(PercentageOffDiscount::fromMappedData(
            ['discount_id' => 'cart-discount', 'data' => '{"percentage":"10"}'],
            ['promo_id' => 'cart-promo'],
            [Condition::class => []],
        ));

        $promos = $this->createMock(PromoRepository::class);
        $promos->method('getAvailablePromos')->willReturn([$promo]);
        $line = $this->createMock(CartLine::class);
        $line->method('getPurchasableReference')->willReturn(new PurchasableReference('variant', 'variant-1'));
        $line->method('getUnitPriceExcl')->willReturn(Money::EUR(10000));
        $line->method('getData')->willReturnCallback(fn (?string $key = null, $default = null) => [
            'sale_price_excl' => '10000',
        ][$key] ?? $default);

        $appliedDiscount = $this->createMock(CartDiscount::class);
        $appliedDiscount->method('getData')->with('promo_id')->willReturn('cart-promo');
        $cart = $this->createMock(Cart::class);
        $cart->method('getLines')->willReturn([$line]);
        $cart->method('getSubtotalExcl')->willReturn(Money::EUR(10000));
        $cart->method('getQuantity')->willReturn(1);
        $cart->method('getAllDiscounts')->willReturn([$appliedDiscount]);

        $query = new FindCartPromoIncentives(new FindPromoSuggestionsForProduct($promos, $this->discountFactory()));

        $this->assertTrue($query->get($cart, 'nl')->isEmpty());
    }

    private function discountFactory(): ApplicableDiscountFactory
    {
        return new ApplicableDiscountFactory(
            [SalePriceProductDiscount::class, PercentageOffOrderSubtotalDiscount::class, FixedAmountOrderSubtotalDiscount::class],
            new ApplicableConditionFactory([MinimumAmountCondition::class, MinimumLinesQuantityCondition::class]),
        );
    }
}
