<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Promo\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;
use Thinktomorrow\Trader\Domain\Model\Promo\Discount;
use Thinktomorrow\Trader\Domain\Model\Promo\Events\PromoCreated;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoState;

final class PromoTest extends TestCase
{
    public function test_it_can_create_a_promo()
    {
        $promo = Promo::create(PromoId::fromString('xxx'), 'foobar', \DateTime::createFromFormat('Y-m-d H:i:s', '2022-02-02 10:10:10'), \DateTime::createFromFormat('Y-m-d H:i:s', '2023-02-02 10:10:10'), false);

        $this->assertEquals([
            'promo_id' => 'xxx',
            'state' => PromoState::online->value,
            'data' => json_encode([]),
            'is_combinable' => false,
            'coupon_code' => 'foobar',
            'start_at' => '2022-02-02 10:10:10',
            'end_at' => '2023-02-02 10:10:10',
        ], $promo->getMappedData());

        $this->assertEquals([
            new PromoCreated(PromoId::fromString('xxx')),
        ], $promo->releaseEvents());
    }

    public function test_it_can_build_discount_from_mapped_data()
    {
        $discount = $this->orderContext->createPromoDiscount('promo-aaa', 'promo-discount-aaa', 'percentage_off', [], [
            MinimumLinesQuantity::fromMappedData([
                'data' => json_encode(['minimum_quantity' => '5']),
            ], []),
        ]);

        $this->assertEquals([
            'promo_id' => 'promo-aaa',
            'discount_id' => 'promo-discount-aaa',
            'key' => 'percentage_off',
            'data' => json_encode(['percentage' => '15']),
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

    public function test_it_can_build_promo_from_mapped_data()
    {
        $promo = $this->orderContext->createPromo('promo-aaa', [], [
            $this->orderContext->createPromoDiscount(),
        ]);

        $this->assertEquals([
            'promo_id' => 'promo-aaa',
            'state' => PromoState::online->value,
            'data' => json_encode([]),
            'is_combinable' => false,
            'coupon_code' => 'PROMO123',
            'start_at' => null,
            'end_at' => null,
        ], $promo->getMappedData());

        $this->assertCount(1, $promo->getChildEntities()[Discount::class]);
        $this->assertEquals([
            Discount::class => [
                [
                    'promo_id' => 'promo-aaa',
                    'discount_id' => 'promo-discount-aaa',
                    'key' => 'percentage_off',
                    'data' => json_encode(['percentage' => '15']),
                ],
            ],
        ], $promo->getChildEntities());
    }

    public function test_it_can_update_discounts()
    {
        $promo = Promo::create(PromoId::fromString('promo-aaa'), 'foobar', \DateTime::createFromFormat('Y-m-d H:i:s', '2022-02-02 10:10:10'), \DateTime::createFromFormat('Y-m-d H:i:s', '2023-02-02 10:10:10'), false);

        $promo->updateDiscounts([
            $this->orderContext->createPromoDiscount(),
        ]);

        $this->assertCount(1, $promo->getChildEntities()[Discount::class]);
        $this->assertEquals([
            Discount::class => [
                [
                    'promo_id' => 'promo-aaa',
                    'discount_id' => 'promo-discount-aaa',
                    'key' => 'percentage_off',
                    'data' => json_encode(['percentage' => '15']),
                ],
            ],
        ], $promo->getChildEntities());
    }

    public function test_it_can_update_state()
    {
        $promo = Promo::create(PromoId::fromString('xxx'), 'foobar', \DateTime::createFromFormat('Y-m-d H:i:s', '2022-02-02 10:10:10'), \DateTime::createFromFormat('Y-m-d H:i:s', '2023-02-02 10:10:10'), false);

        $promo->updateState(PromoState::archived);

        $this->assertEquals(PromoState::archived->value, $promo->getMappedData()['state']);
    }

    public function test_it_can_delete_discount()
    {
        $promo = Promo::create(PromoId::fromString('xxx'), 'foobar', \DateTime::createFromFormat('Y-m-d H:i:s', '2022-02-02 10:10:10'), \DateTime::createFromFormat('Y-m-d H:i:s', '2023-02-02 10:10:10'), false);

        $promo->updateDiscounts([
            $this->orderContext->createPromoDiscount(),
            $this->orderContext->createPromoDiscount(),
        ]);

        $this->assertCount(2, $promo->getChildEntities()[Discount::class]);

        $promo->updateDiscounts([]);

        $this->assertCount(0, $promo->getChildEntities()[Discount::class]);
    }

    public function test_discount_can_add_condition()
    {
        $discount = $this->orderContext->createPromoDiscount();

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

    public function test_discount_can_update_condition()
    {
        $discount = $this->orderContext->createPromoDiscount();

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

    public function test_discount_can_delete_condition()
    {
        $discount = $this->orderContext->createPromoDiscount();

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
