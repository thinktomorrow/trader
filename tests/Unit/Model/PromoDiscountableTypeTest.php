<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\FixedAmountOrderSubtotalDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\PercentageOffOrderSubtotalDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\SalePriceProductDiscount;
use Thinktomorrow\Trader\Application\VatRate\Allocator\ProRateAllocator;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PercentageOffDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\SalePriceSystemDiscount;

final class PromoDiscountableTypeTest extends TestCase
{
    public function test_builtin_discounts_default_to_their_fixed_target_in_both_models(): void
    {
        foreach ([
            [SalePriceSystemDiscount::class, SalePriceProductDiscount::class, '{}', DiscountableType::line],
            [PercentageOffDiscount::class, PercentageOffOrderSubtotalDiscount::class, '{"percentage":"15"}', DiscountableType::order],
            [FixedAmountDiscount::class, FixedAmountOrderSubtotalDiscount::class, '{"amount":"50"}', DiscountableType::order],
        ] as [$domainClass, $applicableClass, $data, $expectedType]) {
            $state = ['discount_id' => 'discount-1', 'data' => $data];
            $promoState = ['promo_id' => 'promo-1', 'coupon_code' => null, 'data' => '{}'];

            $this->assertSame($expectedType, $domainClass::fromMappedData($state, $promoState, [Condition::class => []])->getDiscountableType());
            $this->assertSame($expectedType, $applicableClass::fromMappedData($state, $promoState, [], new VatAllocator(new ProRateAllocator))->getDiscountableType());
        }
    }

    public function test_builtin_discount_rejects_an_incompatible_explicit_target(): void
    {
        $state = ['discount_id' => 'discount-1', 'discountable_type' => 'shipping', 'data' => '{}'];
        $promoState = ['promo_id' => 'promo-1', 'coupon_code' => null, 'data' => '{}'];

        foreach ([
            fn () => SalePriceSystemDiscount::fromMappedData($state, $promoState, [Condition::class => []]),
            fn () => SalePriceProductDiscount::fromMappedData($state, $promoState, [], new VatAllocator(new ProRateAllocator)),
        ] as $build) {
            try {
                $build();
                $this->fail('Expected an incompatible target to be rejected.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString('Invalid discountable_type', $exception->getMessage());
            }
        }
    }
}
