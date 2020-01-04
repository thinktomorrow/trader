<?php

namespace Optiphar\Discounts\Ports;

use Optiphar\Discounts\Conditions\BrandWhitelist;
use Optiphar\Discounts\Conditions\CategoryWhitelist;
use Optiphar\Discounts\Conditions\ConditionKey;
use Optiphar\Discounts\Conditions\ItemBlacklist;
use Optiphar\Discounts\Conditions\ItemWhitelist;
use Optiphar\Discounts\Conditions\MinimumItems;
use Optiphar\Discounts\Discount;
use Optiphar\Discounts\Types\TypeKey;
use Optiphar\Orders\OrderRepository;
use Optiphar\Promos\Common\Domain\Promo;
use Optiphar\Promos\Common\Domain\Rules\DisallowedProductsRule;
use Optiphar\Promos\Common\Domain\Rules\IsCombinable;
use Optiphar\Promos\Common\Domain\Rules\MaximumRedemption;
use Optiphar\Promos\Common\Domain\Rules\ProductWhitelist;
use Optiphar\Promos\Common\Domain\Rules\PromoGroup;
use Optiphar\Promos\Common\Domain\Rules\UniqueRedeemer;
use Optiphar\Promos\Common\Repositories\Promos;
use Optiphar\Promos\Coupon\Domain\CouponPromo;
use Optiphar\Promos\Coupon\Domain\DisallowedProducts;

class DiscountFactory
{
    /** @var Promos */
    private $promoRepository;

    /** @var OrderRepository */
    private $orderRepository;

    public function __construct(Promos $promoRepository, OrderRepository $orderRepository)
    {
        $this->promoRepository = $promoRepository;
        $this->orderRepository = $orderRepository;
    }

    public function createFromPromo(Promo $promo, int $orderId = null): Discount
    {
        // The type of promodiscount related to the new discount type
        $typeKey = TypeKey::fromPromoDiscount($promo->getDiscount());

        return $typeKey->class()::fromPromo($promo, $this->conditions($promo, $orderId), $this->data($promo));
    }

    private function conditions(Promo $promo, int $orderId = null): array
    {
        $conditions = [];

        $current_applies = 0;
        $current_customer_applies = 0;

        // Item blacklist
        $conditions[] = ItemBlacklist::fromRule( new DisallowedProductsRule(app(DisallowedProducts::class)) );

        foreach($promo->getRulesAsArray() as $key => $rule)
        {
            // Ignore the is_combinable rule as it is technically not a rule
            if($rule instanceof IsCombinable) continue;
            if($rule instanceof PromoGroup) continue;

            // ProductWhitelist is handled per product, brand, category in isolation.
            if($rule instanceof ProductWhitelist){
                $conditions[] = ItemWhitelist::fromRule($rule);
                $conditions[] = BrandWhitelist::fromRule($rule);
                $conditions[] = CategoryWhitelist::fromRule($rule);

                continue;
            }
            if($rule instanceof UniqueRedeemer){

                // In case that the cart is already committed, which means there is already an order record created,
                // we'll need to exclude this current order from our customer applies count.
                if(anyCustomer()) {
                    $current_customer_applies = $this->promoRepository->getCustomerApplies($promo->getId(), anyCustomer()->id, $orderId ? [$orderId] : []);
                }

            }

            // Logged in check to assert visitor is in checkout
            if($rule instanceof MaximumRedemption && anyCustomer()){
                $current_applies =  $this->promoRepository->getApplies($promo->getId());
            }

            $conditionKey = ConditionKey::fromRule($rule);
            $conditions[] = $conditionKey->class()::fromRule($rule, [
                'current_applies' => $current_applies,
                'current_customer_applies' => $current_customer_applies,
            ]);
        }

        $this->injectConditionsToMinimumItems($conditions);

        return $conditions;
    }

    private function data(Promo $promo): array
    {
        return [
            'translations' => $this->promoRepository->getAllText($promo->getId())->keyBy('locale')->map->only(['tagline', 'description'])->toArray(),
            'uses_coupon' => ($promo instanceof CouponPromo), // Handy to identify this discount as such for the user
            'is_combinable' => isset($promo->getRulesAsArray()['IsCombinable']),
            'group' => isset($promo->getRulesAsArray()['PromoGroup']) ? $promo->getRulesAsArray()['PromoGroup']->getPersistableValues()['group'] : null,
        ];
    }

    /**
     * After all the conditions for this discount are constructed, we'll still need to make sure that
     * the minimumItems condition can reach out to the other conditions since it calculated
     * its minimum based on only those items that pass the condition checks.
     */
    private function injectConditionsToMinimumItems(array $conditions): void
    {
        foreach ($conditions as $i => $condition) {
            if ( ! $condition instanceof MinimumItems) continue;
            $condition->setOtherConditions(array_except($conditions, $i));
            break;
        }
    }
}
