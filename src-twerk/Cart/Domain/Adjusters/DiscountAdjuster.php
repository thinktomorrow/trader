<?php

namespace Thinktomorrow\Trader\Purchase\Cart\Adjusters;

use Optiphar\Discounts\Application\ApplyApplicableDiscounts;
use Thinktomorrow\Trader\Purchase\Cart\Cart;

class DiscountAdjuster implements Adjuster
{
    /** @var ApplyApplicableDiscounts */
    private $applyApplicableDiscounts;

    public function __construct(ApplyApplicableDiscounts $applyApplicableDiscounts)
    {
        $this->applyApplicableDiscounts = $applyApplicableDiscounts;
    }

    public function adjust(Cart $cart)
    {
        $this->applyApplicableDiscounts->handle($cart);
    }
}
