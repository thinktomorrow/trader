<?php

declare(strict_types=1);

namespace Tests\Unit\Promo;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Promo\Preview\FindPromoSuggestionsForProduct;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewProduct;
use Thinktomorrow\Trader\Application\Promo\Preview\StandardPromoPreviewEligibility;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Promo\CombinationPolicy;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumAmount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PercentageOffDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;

final class FindPromoSuggestionsForProductTest extends TestCase
{
    public function test_it_separates_applicable_and_conditional_promos_and_only_publishes_opted_in_coupons(): void
    {
        $automatic = $this->promo('automatic', null, 1000, [
            'show_on_pdp_when_ready' => true,
            'pdp_description' => ['nl' => 'Nu beschikbaar'],
        ]);
        $coupon = $this->promo('coupon', 'SAVE', 1000, [
            'show_on_pdp_when_ready' => true,
            'show_on_pdp_when_pending' => true,
            'pdp_description' => ['nl' => 'Gebruik de code'],
        ]);
        $hidden = $this->promo('hidden', 'PRIVATE', 1000, []);
        $minimum = $this->promo('minimum', null, 20000, ['show_on_pdp_when_pending' => true]);

        $repository = $this->createMock(PromoRepository::class);
        $repository->method('getAvailablePromos')->willReturn([$automatic, $coupon, $hidden, $minimum]);
        $product = $this->createMock(ProductDetail::class);
        $product->method('isAvailable')->willReturn(true);
        $product->method('getSalePrice')->willReturn(VariantSalePrice::fromScalars('10000', '21', false));
        $product->method('getUnitPrice')->willReturn(VariantUnitPrice::fromScalars('10000', '21', false));

        $query = new FindPromoSuggestionsForProduct($repository, [new StandardPromoPreviewEligibility]);
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

        $query = new FindPromoSuggestionsForProduct($repository, [new StandardPromoPreviewEligibility]);

        $this->assertSame('Voeg nog iets toe en gebruik SAVE', $query->cartIncentivesForProduct(
            PromoPreviewProduct::fromProductDetail($product), 'nl', $cart,
        )->first()->cartDescription);
        $this->assertCount(0, $query->get($product, 'nl')->conditionalPromos);
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
}
