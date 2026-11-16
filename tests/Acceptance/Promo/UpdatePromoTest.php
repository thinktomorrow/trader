<?php

declare(strict_types=1);

namespace Tests\Acceptance\Promo;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Promo\CUD\CreatePromo;
use Thinktomorrow\Trader\Application\Promo\CUD\CreatePromoDiscount;
use Thinktomorrow\Trader\Application\Promo\CUD\UpdatePromo;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

class UpdatePromoTest extends TestCase
{
    public function test_it_can_update_a_promo_without_replacing_discounts()
    {
        $promoId = $this->createPromo();
        $discountId = $this->orderContext->apps()->promoApplication()->createPromoDiscount(new CreatePromoDiscount(
            $promoId->get(), 'fixed_amount', [], ['amount' => '50'],
        ));

        $this->orderContext->apps()->promoApplication()->updatePromo(new UpdatePromo(
            $promoId->get(),
            'foobar-2',
            '2023-02-02 01:10:10',
            '2024-02-02 01:10:10',
            DiscountableType::values(),
            true,
            [
                'poo' => 'bar',
            ]
        ));

        $this->assertInstanceOf(PromoId::class, $promoId);
        $this->assertEquals('foobar-2', $this->orderContext->repos()->promoRepository()->find($promoId)->getCouponCode());
        $this->assertEquals('2023-02-02 01:10:10', $this->orderContext->repos()->promoRepository()->find($promoId)->getMappedData()['start_at']);
        $this->assertEquals('2024-02-02 01:10:10', $this->orderContext->repos()->promoRepository()->find($promoId)->getMappedData()['end_at']);
        $this->assertSame(DiscountableType::cases(), $this->orderContext->repos()->promoRepository()->find($promoId)->getCombinationPolicy()->combinesWith);
        $this->assertTrue($this->orderContext->repos()->promoRepository()->find($promoId)->getCombinationPolicy()->allowsMultipleProductDiscountsPerLine());
        $this->assertEquals(['poo' => 'bar', 'foo' => 'bar'], $this->orderContext->repos()->promoRepository()->find($promoId)->getData());

        $this->assertCount(1, $this->orderContext->repos()->promoRepository()->find($promoId)->getDiscounts());
        $this->assertInstanceOf(FixedAmountDiscount::class, $this->orderContext->repos()->promoRepository()->find($promoId)->findPromoDiscount($discountId));
    }

    public function test_it_can_update_a_promo_without_changing_discount_conditions()
    {
        $promoId = $this->createPromo();
        $discountId = $this->orderContext->apps()->promoApplication()->createPromoDiscount(new CreatePromoDiscount(
            $promoId->get(), 'fixed_amount', [
                ['key' => 'minimum_lines_quantity', 'data' => ['minimum_quantity' => '2']],
            ], ['amount' => '50'],
        ));

        $this->orderContext->apps()->promoApplication()->updatePromo(new UpdatePromo(
            $promoId->get(),
            'foobar-2',
            '2023-02-02 01:10:10',
            '2024-02-02 01:10:10',
            DiscountableType::values(),
            false,
            [
                'poo' => 'bar',
            ]
        ));

        $discounts = $this->orderContext->repos()->promoRepository()->find($promoId)->getDiscounts();

        $this->assertCount(1, $discounts);
        $this->assertInstanceOf(FixedAmountDiscount::class, $discounts[0]);
        $this->assertEquals($discountId, $discounts[0]->getDiscountId());

        $this->assertCount(1, $discounts[0]->getConditions());
        $this->assertInstanceOf(MinimumLinesQuantity::class, $discounts[0]->getConditions()[0]);
    }

    private function createPromo(): PromoId
    {
        return $this->orderContext->apps()->promoApplication()->createPromo(new CreatePromo(
            'foobar',
            '2022-02-02 01:10:10',
            '2023-02-02 01:10:10',
            [],
            false,
            [
                'foo' => 'bar',
            ]
        ));
    }
}
