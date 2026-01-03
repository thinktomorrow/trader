<?php
declare(strict_types=1);

namespace Tests\Acceptance\Promo;

use Thinktomorrow\Trader\Application\Promo\CUD\CreateCouponPromo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

class CreateCouponPromoTest extends PromoContext
{
    public function test_it_can_create_a_coupon_promo()
    {
        $promoId = $this->promoApplication->createPromo(new CreateCouponPromo(
            'foobar',
            '2022-02-02 01:10:10',
            '2023-02-02 01:10:10',
            false,
            [
                'foo' => 'bar',
            ]
        ));

        $this->assertInstanceOf(PromoId::class, $promoId);
        $this->assertEquals($promoId, $this->orderContext->repos()->promoRepository()->find($promoId)->promoId);
        $this->assertEquals('foobar', $this->orderContext->repos()->promoRepository()->find($promoId)->getCouponCode());
        $this->assertEquals('2022-02-02 01:10:10', $this->orderContext->repos()->promoRepository()->find($promoId)->getMappedData()['start_at']);
        $this->assertEquals('2023-02-02 01:10:10', $this->orderContext->repos()->promoRepository()->find($promoId)->getMappedData()['end_at']);
        $this->assertFalse($this->orderContext->repos()->promoRepository()->find($promoId)->getMappedData()['is_combinable']);
        $this->assertEquals(['foo' => 'bar'], $this->orderContext->repos()->promoRepository()->find($promoId)->getData());
    }
}
