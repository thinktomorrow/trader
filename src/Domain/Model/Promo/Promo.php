<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\Discount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\Events\PromoCreated;
use Thinktomorrow\Trader\Domain\Model\Promo\Exceptions\CouldNotFindPromoDiscount;

class Promo implements Aggregate
{
    use HasData;
    use RecordsEvents;

    public readonly PromoId $promoId;

    private PromoState $state;

    private ?string $coupon_code;

    private CombinationPolicy $combinationPolicy;

    /**
     * System promo is a fixed promo that cannot be managed
     * by admin and is set via the codebase.
     */
    private bool $isSystemPromo = false;

    private ?\DateTime $startAt;

    private ?\DateTime $endAt;

    /** @var Discount[] */
    private array $discounts;

    public static function create(PromoId $promoId, ?string $coupon_code, ?\DateTime $startAt, ?\DateTime $endAt, CombinationPolicy $combinationPolicy): static
    {
        $promo = new static;
        $promo->promoId = $promoId;
        $promo->state = PromoState::online;
        $promo->combinationPolicy = $combinationPolicy;
        $promo->coupon_code = $coupon_code;
        $promo->startAt = $startAt;
        $promo->endAt = $endAt;
        $promo->discounts = [];

        $promo->recordEvent(new PromoCreated($promoId));

        return $promo;
    }

    /**
     * System promo is a fixed promo that cannot be managed
     * by admin and is set via the codebase.
     */
    public function isSystemPromo(): bool
    {
        return $this->isSystemPromo;
    }

    public function setAsSystemPromo(): void
    {
        $this->isSystemPromo = true;
    }

    /**
     * Having a coupon code denotes the difference between an automatic applicable promo or
     * a promo that can only be applied when manually entering the corresponding code.
     */
    public function hasCouponCode(): bool
    {
        return (bool) $this->coupon_code;
    }

    public function getCouponCode(): ?string
    {
        return $this->coupon_code;
    }

    public function updateState(PromoState $state): void
    {
        $this->state = $state;
    }

    public function getState(): PromoState
    {
        return $this->state;
    }

    public function addPromoDiscount(Discount $discount): void
    {
        if (! $discount->getPromoId()->equals($this->promoId)) {
            throw new \InvalidArgumentException('Discount does not belong to promo ['.$this->promoId->get().'].');
        }

        foreach ($this->discounts as $existingDiscount) {
            if ($existingDiscount->getDiscountId()->equals($discount->getDiscountId())) {
                throw new \InvalidArgumentException('Discount ['.$discount->getDiscountId()->get().'] already exists on promo ['.$this->promoId->get().'].');
            }
        }

        $this->discounts[] = $discount;
    }

    public function findPromoDiscount(PromoDiscountId $discountId): Discount
    {
        foreach ($this->discounts as $discount) {
            if ($discount->getDiscountId()->equals($discountId)) {
                return $discount;
            }
        }

        throw new CouldNotFindPromoDiscount('No discount found by id ['.$discountId->get().'] on promo ['.$this->promoId->get().'].');
    }

    public function updatePromoDiscount(Discount $discount): void
    {
        $this->findPromoDiscount($discount->getDiscountId());

        if (! $discount->getPromoId()->equals($this->promoId)) {
            throw new \InvalidArgumentException('Discount does not belong to promo ['.$this->promoId->get().'].');
        }

        foreach ($this->discounts as $index => $existingDiscount) {
            if ($existingDiscount->getDiscountId()->equals($discount->getDiscountId())) {
                $this->discounts[$index] = $discount;

                return;
            }
        }
    }

    public function deletePromoDiscount(PromoDiscountId $discountId): void
    {
        $this->findPromoDiscount($discountId);

        foreach ($this->discounts as $index => $discount) {
            if ($discount->getDiscountId()->equals($discountId)) {
                array_splice($this->discounts, $index, 1);

                return;
            }
        }
    }

    public function getDiscounts(): array
    {
        return $this->discounts;
    }

    /** @return list<DiscountableType> */
    public function getDiscountableTypes(): array
    {
        $types = [];

        foreach ($this->discounts as $discount) {
            $type = $discount->getDiscountableType();
            $types[$type->value] = $type;
        }

        return array_values($types);
    }

    public function canCombineWith(self $other): bool
    {
        foreach ($this->getDiscountableTypes() as $type) {
            if (! $other->combinationPolicy->allows($type)) {
                return false;
            }
        }

        foreach ($other->getDiscountableTypes() as $type) {
            if (! $this->combinationPolicy->allows($type)) {
                return false;
            }
        }

        return true;
    }

    public function updateCouponCode(?string $coupon_code): void
    {
        $this->coupon_code = $coupon_code;
    }

    public function updateStartAt(?\DateTime $startAt): void
    {
        $this->startAt = $startAt;
    }

    public function updateEndAt(?\DateTime $endAt): void
    {
        $this->endAt = $endAt;
    }

    public function updateCombinationPolicy(CombinationPolicy $combinationPolicy): void
    {
        $this->combinationPolicy = $combinationPolicy;
    }

    public function getCombinationPolicy(): CombinationPolicy
    {
        return $this->combinationPolicy;
    }

    public function getMappedData(): array
    {
        return [
            'promo_id' => $this->promoId->get(),
            'state' => $this->state->value,
            'combines_with' => json_encode($this->combinationPolicy->toArray()),
            'multiple_product_discounts_per_line' => $this->combinationPolicy->allowsMultipleProductDiscountsPerLine(),
            'is_system_promo' => $this->isSystemPromo,
            'coupon_code' => $this->coupon_code,
            'start_at' => $this->startAt?->format('Y-m-d H:i:s'),
            'end_at' => $this->endAt?->format('Y-m-d H:i:s'),
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Discount::class => array_map(fn (Discount $discount) => $discount->getMappedData(), $this->discounts),
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        Assertion::allIsInstanceOf($childEntities[Discount::class], Discount::class);

        $promo = new static;

        $promo->promoId = PromoId::fromString($state['promo_id']);
        $promo->state = PromoState::from($state['state']);
        $promo->combinationPolicy = CombinationPolicy::fromArray(json_decode($state['combines_with'], true, flags: JSON_THROW_ON_ERROR), (bool) $state['multiple_product_discounts_per_line']);
        $promo->isSystemPromo = $state['is_system_promo'];
        $promo->coupon_code = $state['coupon_code'];
        $promo->startAt = $state['start_at'] ? \DateTime::createFromFormat('Y-m-d H:i:s', $state['start_at']) : null;
        $promo->endAt = $state['end_at'] ? \DateTime::createFromFormat('Y-m-d H:i:s', $state['end_at']) : null;
        $promo->data = json_decode($state['data'], true);
        $promo->discounts = $childEntities[Discount::class];

        //        $promo->discounts = array_key_exists(Discount::class, $childEntities)
        //            ? array_map(fn ($discountState) => Discount::fromMappedData($discountState, $state, [Condition::class => $discountState[Condition::class]]), $childEntities[Discount::class])
        //            : [];

        return $promo;
    }
}
