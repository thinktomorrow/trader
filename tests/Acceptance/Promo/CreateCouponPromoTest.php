<?php
declare(strict_types=1);

namespace Tests\Acceptance\Promo;

use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Application\Promo\CUD\UpdateDiscounts;
use Thinktomorrow\Trader\Application\Promo\CUD\CreateCouponPromo;

class CreateCouponPromoTest extends PromoContext
{
    /** @test */
    public function it_can_create_a_coupon_promo()
    {
        $promoId = $this->promoApplication->createPromo(new CreateCouponPromo(
            'foobar',
            '2022-02-02 01:10:10',
            '2023-02-02 01:10:10',
            false,
            [
                'foo' => 'bar'
            ]
        ));

        $this->assertInstanceOf(PromoId::class, $promoId);
        $this->assertEquals($promoId, $this->promoRepository->find($promoId)->promoId);
        $this->assertEquals('foobar', $this->promoRepository->find($promoId)->getCouponCode());
        $this->assertEquals('2022-02-02 01:10:10', $this->promoRepository->find($promoId)->getMappedData()['start_at']);
        $this->assertEquals('2023-02-02 01:10:10', $this->promoRepository->find($promoId)->getMappedData()['end_at']);
        $this->assertFalse($this->promoRepository->find($promoId)->getMappedData()['is_combinable']);
        $this->assertEquals(['foo' => 'bar'], $this->promoRepository->find($promoId)->getData());
    }
}
