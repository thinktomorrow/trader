<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

class CreatePromoDiscount
{
    public function __construct(
        private string $promoId,
        private string $key,
        private array $conditions,
        private array $data,
        private ?DiscountableType $discountableType = null,
        private ?string $discountId = null,
    ) {}

    public function getPromoId(): PromoId
    {
        return PromoId::fromString($this->promoId);
    }

    public function getDiscountId(): ?PromoDiscountId
    {
        return $this->discountId ? PromoDiscountId::fromString($this->discountId) : null;
    }

    public function getMapKey(): string
    {
        return $this->key;
    }

    /** @return ConditionDTO[] */
    public function getConditions(): array
    {
        return array_map(fn (array $payload) => new ConditionDTO(...$payload), $this->conditions);
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getDiscountableType(): ?DiscountableType
    {
        return $this->discountableType;
    }
}
