<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Model\Promo\CombinationPolicy;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;

final class CombinationPolicyTest extends TestCase
{
    public function test_discountable_types_use_line_for_line_discounts(): void
    {
        $this->assertSame(['line', 'order', 'shipping', 'payment'], DiscountableType::values());
        $this->assertSame(DiscountableType::line, DiscountableType::from('line'));
    }

    public function test_product_discounts_on_one_line_stack_only_when_both_promos_allow_it(): void
    {
        $allowing = new CombinationPolicy([DiscountableType::line], true);
        $alsoAllowing = new CombinationPolicy([DiscountableType::line], true);
        $default = new CombinationPolicy([DiscountableType::line]);

        $this->assertTrue($allowing->allowsProductStackingWith($alsoAllowing));
        $this->assertFalse($allowing->allowsProductStackingWith($default));
        $this->assertFalse($default->allowsProductStackingWith($allowing));
        $this->assertFalse($default->allowsMultipleProductDiscountsPerLine());
    }
}
