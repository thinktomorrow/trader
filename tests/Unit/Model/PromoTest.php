<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Promo\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;
use Thinktomorrow\Trader\Domain\Model\Promo\Discount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PercentageOffDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Events\PromoCreated;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoState;

final class PromoTest extends TestCase
{
    /** @test */
    public function it_can_create_a_promo()
    {
        $promo = Promo::create(PromoId::fromString('xxx'));

        $this->assertEquals([
            'promo_id' => 'xxx',
            'state' => PromoState::online->value,
            'data' => json_encode([]),
            'coupon_code' => null,
            'start_at' => null,
            'end_at' => null,
        ], $promo->getMappedData());

        $this->assertEquals([
            new PromoCreated(PromoId::fromString('xxx')),
        ], $promo->releaseEvents());
    }

    /** @test */
    public function it_can_build_discount_from_mapped_data()
    {
        $discount = $this->createDiscount();

        $this->assertEquals([
            'promo_id' => 'xxx',
            'key' => 'fixed_amount',
            'data' => json_encode(['amount' => '40']),
        ], $discount->getMappedData());


        $this->assertCount(1, $discount->getChildEntities()[Condition::class]);

        $this->assertEquals([
            Condition::class => [
                [
                    'key' => 'minimum_lines_quantity',
                    'data' => json_encode(['minimum_quantity' => 5]),
                ],
            ],
        ], $discount->getChildEntities());
    }

    /** @test */
    public function it_can_build_promo_from_mapped_data()
    {
        $promo = $this->createPromo([
            'coupon_code' => 'foobar',
            'start_at' => '2022-05-05 10:10:10',
            'end_at' => '2022-05-09 10:10:10',
            'data' => json_encode(['foo' => 'bar']),
        ], [
            Discount::class => [
                PercentageOffDiscount::fromMappedData([
                    'data' => json_encode(['percentage' => '40']),
                ], ['promo_id' => 'xxx'], [
                    Condition::class => [
                        MinimumLinesQuantity::fromMappedData([
                            'data' => json_encode(['minimum_quantity' => '5']),
                        ], []),
                    ],
                ]),
            ],
        ]);

        $this->assertEquals([
            'promo_id' => 'xxx',
            'state' => PromoState::online->value,
            'data' => json_encode(['foo' => 'bar']),
            'coupon_code' => 'foobar',
            'start_at' => '2022-05-05 10:10:10',
            'end_at' => '2022-05-09 10:10:10',
        ], $promo->getMappedData());

        $this->assertCount(1, $promo->getChildEntities()[Discount::class]);
        $this->assertEquals([
            Discount::class => [
                [
                    'promo_id' => 'xxx',
                    'key' => 'percentage_off',
                    'data' => json_encode(['percentage' => '40']),
                ],
            ],
        ], $promo->getChildEntities());
    }

    /** @test */
    public function it_can_update_discounts()
    {
        $promo = Promo::create(PromoId::fromString('xxx'));

        $promo->updateDiscounts([
            $this->createDiscount(),
        ]);

        $this->assertCount(1, $promo->getChildEntities()[Discount::class]);
        $this->assertEquals([
            Discount::class => [
                [
                    'promo_id' => 'xxx',
                    'key' => 'fixed_amount',
                    'data' => json_encode(['amount' => '40']),
                ],
            ],
        ], $promo->getChildEntities());
    }

    /** @test */
    public function it_can_update_state()
    {
        $promo = Promo::create(PromoId::fromString('xxx'));

        $promo->updateState(PromoState::archived);

        $this->assertEquals(PromoState::archived->value, $promo->getMappedData()['state']);
    }

    /** @test */
    public function it_can_delete_discount()
    {
        $promo = Promo::create(PromoId::fromString('xxx'));

        $promo->updateDiscounts([
            $this->createDiscount(),
            $this->createDiscount(),
        ]);

        $this->assertCount(2, $promo->getChildEntities()[Discount::class]);

        $promo->updateDiscounts([]);

        $this->assertCount(0, $promo->getChildEntities()[Discount::class]);
    }

    /** @test */
    public function discount_can_add_condition()
    {
        $discount = $this->createDiscount();

        $discount->updateConditions([
            MinimumLinesQuantity::fromMappedData([
                'data' => json_encode(['minimum_quantity' => 10]),
            ], $discount->getMappedData()),
        ]);

        $this->assertCount(1, $discount->getChildEntities()[Condition::class]);
        $this->assertEquals([
            Condition::class => [
                [
                    'key' => 'minimum_lines_quantity',
                    'data' => json_encode(['minimum_quantity' => 10]),
                ],
            ],
        ], $discount->getChildEntities());
    }

    /** @test */
    public function discount_can_update_condition()
    {
        $discount = $this->createDiscount();

        $discount->updateConditions([
            MinimumLinesQuantity::fromMappedData([
                'data' => json_encode(['minimum_quantity' => 10]),
            ], $discount->getMappedData()),
        ]);

        $discount->updateConditions([
            MinimumLinesQuantity::fromMappedData([
                'data' => json_encode(['minimum_quantity' => 20]),
            ], $discount->getMappedData()),
        ]);

        $this->assertCount(1, $discount->getChildEntities()[Condition::class]);
        $this->assertEquals([
            Condition::class => [
                [
                    'key' => 'minimum_lines_quantity',
                    'data' => json_encode(['minimum_quantity' => 20]),
                ],
            ],
        ], $discount->getChildEntities());
    }

    /** @test */
    public function discount_can_delete_condition()
    {
        $discount = $this->createDiscount();

        $discount->updateConditions([
            MinimumLinesQuantity::fromMappedData([
                'data' => json_encode(['minimum_quantity' => 10]),
            ], $discount->getMappedData()),
        ]);

        $this->assertCount(1, $discount->getChildEntities()[Condition::class]);

        $discount->updateConditions([]);

        $this->assertCount(0, $discount->getChildEntities()[Condition::class]);
    }
}
