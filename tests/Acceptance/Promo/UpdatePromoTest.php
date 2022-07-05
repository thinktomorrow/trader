<?php
declare(strict_types=1);

namespace Tests\Acceptance\Promo;

use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Application\Promo\CUD\UpdatePromo;
use Thinktomorrow\Trader\Application\Promo\CUD\CreateCouponPromo;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;

class UpdatePromoTest extends PromoContext
{
    /** @test */
    public function it_can_update_a_promo_with_discounts()
    {
        $promoId = $this->createPromo();

        $this->promoApplication->updatePromo(new UpdatePromo(
            $promoId->get(),
            'foobar-2',
            '2023-02-02 01:10:10',
            '2024-02-02 01:10:10',
            true,
            [
                [
                    'discountId' => null,
                    'key' => 'fixed_amount',
                    'conditions' => [],
                    'data' => [
                        'amount' => '50',
                        'foo' => 'bar'
                    ],
                ],
            ],
            [
                'poo' => 'bar'
            ]
        ));

        $this->assertInstanceOf(PromoId::class, $promoId);
        $this->assertEquals('foobar-2', $this->promoRepository->find($promoId)->getCouponCode());
        $this->assertEquals('2023-02-02 01:10:10', $this->promoRepository->find($promoId)->getMappedData()['start_at']);
        $this->assertEquals('2024-02-02 01:10:10', $this->promoRepository->find($promoId)->getMappedData()['end_at']);
        $this->assertTrue($this->promoRepository->find($promoId)->getMappedData()['is_combinable']);
        $this->assertEquals(['poo' => 'bar', 'foo' => 'bar'], $this->promoRepository->find($promoId)->getData());

        $this->assertCount(1, $this->promoRepository->find($promoId)->getDiscounts());
        $this->assertInstanceOf(FixedAmountDiscount::class, $this->promoRepository->find($promoId)->getDiscounts()[0]);
    }

    /** @test */
    public function it_can_update_a_promo_with_discount_and_conditions()
    {
        $promoId = $this->createPromo();

        $this->promoApplication->updatePromo(new UpdatePromo(
            $promoId->get(),
            'foobar-2',
            '2023-02-02 01:10:10',
            '2024-02-02 01:10:10',
            true,
            [
                [
                    'discountId' => null,
                    'key' => 'fixed_amount',
                    'conditions' => [
                        [
                            'key' => 'minimum_lines_quantity',
                            'data' => [
                                'minimum_quantity' => '2',
                                'foo' => 'bar',
                            ],
                        ],
                    ],
                    'data' => [
                        'amount' => '50',
                        'foo' => 'bar'
                    ],
                ],
            ],
            [
                'poo' => 'bar'
            ]
        ));

        $discounts = $this->promoRepository->find($promoId)->getDiscounts();

        $this->assertCount(1, $discounts);
        $this->assertInstanceOf(FixedAmountDiscount::class, $discounts[0]);

        $this->assertCount(1, $discounts[0]->getConditions());
        $this->assertInstanceOf(MinimumLinesQuantity::class, $discounts[0]->getConditions()[0]);
    }

    private function createPromo(): PromoId
    {
        return $this->promoApplication->createPromo(new CreateCouponPromo(
            'foobar',
            '2022-02-02 01:10:10',
            '2023-02-02 01:10:10',
            false,
            [
                'foo' => 'bar'
            ]
        ));
    }
}
