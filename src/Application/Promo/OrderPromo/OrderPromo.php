<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo;

use Thinktomorrow\Trader\Application\Promo\LinePromo\LineDiscount;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

class OrderPromo
{
    use HasData;

    public readonly PromoId $promoId;
    private bool $isCombinable;
    private bool $isSystemPromo;
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

    public function isCombinable(): bool
    {
        return $this->isCombinable;
    }

    public function isSystemPromo(): bool
    {
        return $this->isSystemPromo;
    }

    public function getCombinedDiscountPrice(Order $order): DiscountPrice
    {
        return array_reduce($this->discounts, fn ($carry, OrderDiscount $discount) => $discount->getCombinedDiscountPrice($order), DefaultDiscountPrice::zero());
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        self::validateDiscounts($childEntities[OrderDiscount::class]);

        $promo = new static();

        $promo->promoId = PromoId::fromString($state['promo_id']);
        $promo->isCombinable = (bool)$state['is_combinable'];
        $promo->isSystemPromo = (bool)$state['is_system_promo'];
        $promo->coupon_code = $state['coupon_code'];
        $promo->data = json_decode($state['data'], true);
        $promo->discounts = $childEntities[OrderDiscount::class];

        return $promo;
    }

    private static function validateDiscounts($discounts): void
    {
        foreach ($discounts as $discount) {
            if (! $discount instanceof OrderDiscount && ! $discount instanceof LineDiscount) {
                throw new \InvalidArgumentException('Invalid discount type [' . $discount::class . '] provided in child entities for OrderPromo.');
            }
        }
    }
}
