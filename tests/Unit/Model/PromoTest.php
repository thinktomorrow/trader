<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Promo\CombinationPolicy;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\Discount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\SalePriceSystemDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Events\PromoCreated;
use Thinktomorrow\Trader\Domain\Model\Promo\Exceptions\CouldNotFindPromoDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoState;

final class PromoTest extends TestCase
{
    public function test_it_can_create_a_promo()
    {
        $promo = Promo::create(PromoId::fromString('xxx'), 'foobar', \DateTime::createFromFormat('Y-m-d H:i:s', '2022-02-02 10:10:10'), \DateTime::createFromFormat('Y-m-d H:i:s', '2023-02-02 10:10:10'), new CombinationPolicy([]));

        $this->assertEquals([
            'promo_id' => 'xxx',
            'state' => PromoState::online->value,
            'data' => json_encode([]),
            'combines_with' => '[]',
            'multiple_product_discounts_per_line' => false,
            'is_system_promo' => false,
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
            'discountable_type' => 'order',
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
            'combines_with' => '[]',
            'multiple_product_discounts_per_line' => false,
            'is_system_promo' => false,
            'coupon_code' => null,
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
                    'discountable_type' => 'order',
                    'data' => json_encode(['percentage' => '15']),
                ],
            ],
        ], $promo->getChildEntities());
    }

    public function test_it_can_add_and_find_a_promo_discount()
    {
        $promo = Promo::create(PromoId::fromString('promo-aaa'), 'foobar', \DateTime::createFromFormat('Y-m-d H:i:s', '2022-02-02 10:10:10'), \DateTime::createFromFormat('Y-m-d H:i:s', '2023-02-02 10:10:10'), new CombinationPolicy([]));

        $discount = $this->orderContext->createPromoDiscount();
        $promo->addPromoDiscount($discount);

        $this->assertSame($discount, $promo->findPromoDiscount($discount->getDiscountId()));

        $this->assertCount(1, $promo->getChildEntities()[Discount::class]);
        $this->assertEquals([
            Discount::class => [
                [
                    'promo_id' => 'promo-aaa',
                    'discount_id' => 'promo-discount-aaa',
                    'key' => 'percentage_off',
                    'discountable_type' => 'order',
                    'data' => json_encode(['percentage' => '15']),
                ],
            ],
        ], $promo->getChildEntities());
    }

    public function test_both_promos_must_allow_every_type_of_a_multi_type_promo(): void
    {
        $bundle = $this->promoWithDiscountTypes('bundle', [DiscountableType::line, DiscountableType::order], [DiscountableType::order]);
        $orderOnly = $this->promoWithDiscountTypes('order-only', [DiscountableType::order], [DiscountableType::line]);

        $this->assertFalse($bundle->canCombineWith($orderOnly));
        $this->assertFalse($orderOnly->canCombineWith($bundle));
    }

    public function test_mutual_permission_can_combine_distinct_types(): void
    {
        $line = $this->promoWithDiscountTypes('line', [DiscountableType::line], [DiscountableType::order]);
        $order = $this->promoWithDiscountTypes('order', [DiscountableType::order], [DiscountableType::line]);

        $this->assertTrue($line->canCombineWith($order));
        $this->assertTrue($order->canCombineWith($line));
    }

    public function test_one_sided_permission_does_not_combine(): void
    {
        $line = $this->promoWithDiscountTypes('line', [DiscountableType::line], [DiscountableType::order]);
        $order = $this->promoWithDiscountTypes('order', [DiscountableType::order], []);

        $this->assertFalse($line->canCombineWith($order));
        $this->assertFalse($order->canCombineWith($line));
    }

    public function test_it_can_update_state()
    {
        $promo = Promo::create(PromoId::fromString('xxx'), 'foobar', \DateTime::createFromFormat('Y-m-d H:i:s', '2022-02-02 10:10:10'), \DateTime::createFromFormat('Y-m-d H:i:s', '2023-02-02 10:10:10'), new CombinationPolicy([]));

        $promo->updateState(PromoState::archived);

        $this->assertEquals(PromoState::archived->value, $promo->getMappedData()['state']);
    }

    public function test_it_can_delete_discount()
    {
        $promo = Promo::create(PromoId::fromString('xxx'), 'foobar', \DateTime::createFromFormat('Y-m-d H:i:s', '2022-02-02 10:10:10'), \DateTime::createFromFormat('Y-m-d H:i:s', '2023-02-02 10:10:10'), new CombinationPolicy([]));

        $promo->addPromoDiscount($this->orderContext->createPromoDiscount('xxx', 'first'));
        $promo->addPromoDiscount($this->orderContext->createPromoDiscount('xxx', 'second'));

        $this->assertCount(2, $promo->getChildEntities()[Discount::class]);

        $promo->deletePromoDiscount(PromoDiscountId::fromString('first'));

        $this->assertCount(1, $promo->getChildEntities()[Discount::class]);
        $this->assertSame('second', $promo->getDiscounts()[0]->getDiscountId()->get());
    }

    public function test_it_can_update_one_discount_without_replacing_the_others(): void
    {
        $promo = Promo::create(PromoId::fromString('xxx'), null, null, null, new CombinationPolicy([]));
        $promo->addPromoDiscount($this->orderContext->createPromoDiscount('xxx', 'first'));
        $other = $this->orderContext->createPromoDiscount('xxx', 'second');
        $promo->addPromoDiscount($other);
        $replacement = $this->orderContext->createPromoDiscount('xxx', 'first', 'fixed_amount', ['data' => json_encode(['amount' => '50'])]);

        $promo->updatePromoDiscount($replacement);

        $this->assertSame($replacement, $promo->findPromoDiscount(PromoDiscountId::fromString('first')));
        $this->assertSame($other, $promo->findPromoDiscount(PromoDiscountId::fromString('second')));
    }

    public function test_it_rejects_unknown_promo_discounts(): void
    {
        $promo = Promo::create(PromoId::fromString('xxx'), null, null, null, new CombinationPolicy([]));

        $this->expectException(CouldNotFindPromoDiscount::class);
        $promo->deletePromoDiscount(PromoDiscountId::fromString('missing'));
    }

    public function test_it_rejects_a_discount_belonging_to_another_promo(): void
    {
        $promo = Promo::create(PromoId::fromString('xxx'), null, null, null, new CombinationPolicy([]));

        $this->expectException(\InvalidArgumentException::class);
        $promo->addPromoDiscount($this->orderContext->createPromoDiscount('other-promo', 'first'));
    }

    public function test_it_rejects_duplicate_discount_ids(): void
    {
        $promo = Promo::create(PromoId::fromString('xxx'), null, null, null, new CombinationPolicy([]));
        $promo->addPromoDiscount($this->orderContext->createPromoDiscount('xxx', 'first'));

        $this->expectException(\InvalidArgumentException::class);
        $promo->addPromoDiscount($this->orderContext->createPromoDiscount('xxx', 'first'));
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

    /**
     * @param list<DiscountableType> $types
     * @param list<DiscountableType> $combinesWith
     */
    private function promoWithDiscountTypes(string $id, array $types, array $combinesWith): Promo
    {
        $promo = Promo::create(PromoId::fromString($id), null, null, null, new CombinationPolicy($combinesWith));

        foreach ($types as $index => $type) {
            $discountId = $id.'-discount-'.$index;

            $promo->addPromoDiscount($type === DiscountableType::line
                ? SalePriceSystemDiscount::fromMappedData(
                    ['discount_id' => $discountId, 'data' => '{}'],
                    ['promo_id' => $id],
                    [Condition::class => []],
                )
                : $this->orderContext->createPromoDiscount($id, $discountId));
        }

        return $promo;
    }
}
