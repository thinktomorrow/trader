<?php

namespace Thinktomorrow\Trader\Purchase\Cart\Adjusters;

use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Optiphar\Discounts\Application\ApplyApplicableDiscounts;

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
