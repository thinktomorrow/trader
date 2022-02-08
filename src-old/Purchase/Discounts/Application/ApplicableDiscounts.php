<?php

namespace Purchase\Discounts\Application;

use Optiphar\Discounts\Discount;
use Illuminate\Support\Collection;
use Optiphar\Promos\Common\Domain\Promo;
use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Optiphar\Promos\Common\Repositories\Promos;
use Purchase\Discounts\Domain\Ports\DiscountFactory;
use Purchase\Discounts\Domain\Types\FreeProductsDiscount;
use Purchase\Discounts\Domain\Types\ProductPercentageOffDiscount;
use Purchase\Discounts\Domain\Types\CheapestProductPercentageOffDiscount;
use function collect;

class ApplicableDiscounts
{
    /** @var Promos */
    private $promoRepository;

    /** @var DiscountFactory */
    private $discountFactory;

    public function __construct(Promos $promoRepository, DiscountFactory $discountFactory)
    {
        $this->promoRepository = $promoRepository;
        $this->discountFactory = $discountFactory;
    }

    public function get(Cart $cart): Collection
    {
        $discounts = $this->enabledDiscounts($cart);

        $discounts = $discounts->filter(function (Discount $discount) use ($cart) {
            return $discount->overallApplicable($cart);
        });

        if($discounts->isEmpty()) return collect();

        $discounts = $this->sortDiscountsByHighestImpact($cart, $discounts);
        $discounts = $this->sortDiscountsByCouponFirst($cart, $discounts);
        $discounts = $this->filterDiscountsByCombinability($cart, $discounts);
        $discounts = $this->rejectDiscountsByGroup($cart, $discounts);
        $discounts = $this->sortDiscountsByItemDiscountsFirst($cart, $discounts);

        return $discounts;
    }

    public function enabledDiscounts(Cart $cart): Collection
    {
        $promos = $this->promoRepository->getActiveOrderPromos();

        return $promos->map(function(Promo $promo) use($cart){
            return $this->discountFactory->createFromPromo($promo, $cart->orderId());
        });
    }

    /**
     * We currently do not allow stacking up multiple discounts and allow only one to be used
     * per order. Only the discount with the highest impact on the order is selected.
     * So max. one discount per order and in case of item discounts max. one per item.
     *
     * The only exception is when discounts have a reference to 'children' discounts.
     * In that case, these discounts are also added to the order. This makes it
     * possible to add both a general discount and e.g. free shipping.
     *
     * @param Cart $cart
     * @param Collection $discounts
     * @return Collection
     */
    private function filterDiscountsByCombinability(Cart $cart, Collection $discounts): Collection
    {
        // Extract first discount (highest impact one) and check others for combinability
        return collect(array_merge(
            [$discounts->shift()],
            $this->filterCombinableDiscounts($discounts)->all()
        ));
    }

    /**
     * Discounts belonging to the same group cannot be combined.
     *
     * @param Cart $cart
     * @param Collection $discounts
     * @return Collection
     */
    private function rejectDiscountsByGroup(Cart $cart, Collection $discounts): Collection
    {
        $groupOccurrences = [];

        return $discounts->reject(function (Discount $discount) use(&$groupOccurrences){
            if($discount->group()) {

                if(in_array($discount->group(), $groupOccurrences)) {
                    return true;
                }

                $groupOccurrences[] = $discount->group();
            }

            return false;
        });
    }

    /**
     * The first discount (with highest impact) is selected regardless of anything else.
     * All other discounts are only added if they are set to be combinable.
     *
     * @param Collection $discounts
     * @return Collection
     */
    private function filterCombinableDiscounts(Collection $discounts): Collection
    {
        return $discounts->filter(function (Discount $discount){
            return $discount->isCombinable();
        });
    }

    private function sortDiscountsByHighestImpact(Cart $cart, Collection $discounts): Collection
    {
        return $discounts->sortByDesc(function (Discount $discount) use ($cart) {
            return $discount->discountAmountTotal($cart)->getAmount();
        });
    }

    private function sortDiscountsByItemDiscountsFirst(Cart $cart, Collection $discounts): Collection
    {
        return $discounts->sortBy(function($discount){
            return in_array(get_class($discount), [
                ProductPercentageOffDiscount::class,
                CheapestProductPercentageOffDiscount::class,
                FreeProductsDiscount::class,
            ]) ? 0 : 1;
        });
    }

    private function sortDiscountsByCouponFirst(Cart $cart, Collection $discounts)
    {
        return $discounts->sortBy(function(Discount $discount){
            return $discount->usesCondition('coupon') ? 0 : 1;
        });
    }
}
