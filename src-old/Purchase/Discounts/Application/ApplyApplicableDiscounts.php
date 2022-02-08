<?php

namespace Purchase\Discounts\Application;

use Optiphar\Cart\Cart;
use Optiphar\Discounts\Discount;
use Purchase\Discounts\Domain\Exceptions\CannotApplyDiscount;
use function Optiphar\Discounts\Application\report;

class ApplyApplicableDiscounts
{
    /** @var ApplicableDiscounts */
    private $applicableDiscounts;

    public function __construct(ApplicableDiscounts $applicableDiscounts)
    {
        $this->applicableDiscounts = $applicableDiscounts;
    }

    public function handle(Cart $cart)
    {
        $applicableDiscounts = $this->applicableDiscounts->get($cart);

        $applicableDiscounts->each(function(Discount $discount) use($cart){
            try {
                $discount->apply($cart);
            } catch (CannotApplyDiscount $e) {

                // Can occur when an already applied discount becomes inapplicable in the
                // current context. E.g. due to conditions that are no longer valid
                report($e);
            }
        });
    }
}
