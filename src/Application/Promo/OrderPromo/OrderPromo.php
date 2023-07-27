<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

class OrderPromo
{
    use HasData;

    public readonly PromoId $promoId;
    private bool $isCombinable;
    private ?string $coupon_code;

    /** @var OrderDiscount[] */
    private array $discounts;

    public function getDiscounts(): array
    {
        return $this->discounts;
    }

    public function getCouponCode(): ?string
    {
        return $this->coupon_code;
    }

    //    public function apply(Order $order): void
    //    {
    //        $hasBeenApplied = false;
    //
    //        // TODO: check if order is in customer hands still? Or can admin add promo afterwards??
    //
    //        // Loop over different discountables
    //        foreach ($this->discounts as $discount) {
    //            foreach ($order->getShippings() as $shipping) {
    //                if ($discount->isApplicable($order, $shipping)) {
    //                    $discount->apply($order, $shipping);
    //                    $hasBeenApplied = true;
    //                }
    //            }
    //
    //            foreach ($order->getLines() as $line) {
    //                if ($discount->isApplicable($order, $line)) {
    //                    $discount->apply($order, $line);
    //                    $hasBeenApplied = true;
    //                }
    //            }
    //
    //            if ($discount->isApplicable($order, $order)) {
    //                $discount->apply($order, $order);
    //                $hasBeenApplied = true;
    //            }
    //        }
    //
    //        if ($this->coupon_code && $hasBeenApplied) {
    //            $order->setEnteredCouponCode($this->coupon_code);
    //        }
    //    }

    public function isCombinable(): bool
    {
        return $this->isCombinable;
    }

    public function getCombinedDiscountTotal(Order $order): DiscountTotal
    {
        return array_reduce($this->discounts, fn ($carry, OrderDiscount $discount) => $discount->getCombinedDiscountTotal($order), DiscountTotal::zero());
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        Assertion::allIsInstanceOf($childEntities[OrderDiscount::class], OrderDiscount::class);

        $promo = new static();

        $promo->promoId = PromoId::fromString($state['promo_id']);
        $promo->isCombinable = $state['is_combinable'];
        $promo->coupon_code = $state['coupon_code'];
        $promo->data = json_decode($state['data'], true);
        $promo->discounts = $childEntities[OrderDiscount::class];

        return $promo;
    }
}
