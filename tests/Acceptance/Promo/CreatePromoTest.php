<?php

declare(strict_types=1);

namespace Tests\Acceptance\Promo;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Promo\CUD\CreatePromo;
use Thinktomorrow\Trader\Application\Promo\CUD\CreateSystemPromo;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\SalePriceSystemDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

class CreatePromoTest extends TestCase
{
    public function test_it_can_create_a_coupon_promo()
    {
        $promoId = $this->orderContext->apps()->promoApplication()->createPromo(new CreatePromo(
            'foobar',
            '2022-02-02 01:10:10',
            '2023-02-02 01:10:10',
            [],
            true,
            [
                'foo' => 'bar',
            ]
        ));

        $this->assertInstanceOf(PromoId::class, $promoId);
        $this->assertEquals($promoId, $this->orderContext->repos()->promoRepository()->find($promoId)->promoId);
        $this->assertEquals('foobar', $this->orderContext->repos()->promoRepository()->find($promoId)->getCouponCode());
        $this->assertEquals('2022-02-02 01:10:10', $this->orderContext->repos()->promoRepository()->find($promoId)->getMappedData()['start_at']);
        $this->assertEquals('2023-02-02 01:10:10', $this->orderContext->repos()->promoRepository()->find($promoId)->getMappedData()['end_at']);
        $this->assertSame([], $this->orderContext->repos()->promoRepository()->find($promoId)->getCombinationPolicy()->toArray());
        $this->assertTrue($this->orderContext->repos()->promoRepository()->find($promoId)->getCombinationPolicy()->allowsMultipleProductDiscountsPerLine());
        $this->assertEquals(['foo' => 'bar'], $this->orderContext->repos()->promoRepository()->find($promoId)->getData());
    }

    public function test_system_promo_can_be_activated_by_code_without_becoming_automatic(): void
    {
        $repository = $this->orderContext->repos()->promoRepository();
        $id = $this->orderContext->apps()->promoApplication()->createSystemPromo(
            new CreateSystemPromo('system-code', DiscountableType::values(), 'SYSTEM', true, []),
        );

        $this->assertTrue($repository->find($id)->isSystemPromo());
        $this->assertTrue($repository->find($id)->getCombinationPolicy()->allowsMultipleProductDiscountsPerLine());
        $this->assertSame('system-code', $repository->findAvailableByCouponCode('SYSTEM')->promoId->get());
        $this->assertNotContains($id->get(), array_map(fn ($promo) => $promo->promoId->get(), $repository->getAvailableAutomaticPromos()));
    }

    public function test_sale_price_system_promo_has_its_discount(): void
    {
        $promo = $this->orderContext->repos()->promoRepository()->find(PromoId::fromString('system_sale_price'));

        $this->assertTrue($promo->isSystemPromo());
        $this->assertCount(1, $promo->getDiscounts());
        $this->assertInstanceOf(SalePriceSystemDiscount::class, $promo->getDiscounts()[0]);
        $this->assertSame(SalePriceSystemDiscount::getMapKey(), $promo->getDiscounts()[0]->getDiscountId()->get());
    }
}
