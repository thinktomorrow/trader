<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

class UpdatePromo
{
    private string $promoId;

    private ?string $couponCode;

    private ?string $startAt;

    private ?string $endAt;

    private array $data;

    /** @param list<string> $combinesWith */
    public function __construct(string $promoId, ?string $couponCode, ?string $startAt, ?string $endAt, private array $combinesWith, private bool $multipleProductDiscountsPerLine, array $data)
    {
        $this->promoId = $promoId;
        $this->couponCode = $couponCode;
        $this->startAt = $startAt;
        $this->endAt = $endAt;
        $this->data = $data;
    }

    public function getPromoId(): PromoId
    {
        return PromoId::fromString($this->promoId);
    }

    public function getCouponCode(): ?string
    {
        return $this->couponCode;
    }

    public function getStartAt(): ?\DateTime
    {
        return $this->startAt ? new \DateTime($this->startAt) : null;
    }

    public function getEndAt(): ?\DateTime
    {
        return $this->endAt ? new \DateTime($this->endAt) : null;
    }

    /** @return list<DiscountableType> */
    public function getCombinesWith(): array
    {
        return array_map(fn (string $type): DiscountableType => DiscountableType::from($type), $this->combinesWith);
    }

    public function allowsMultipleProductDiscountsPerLine(): bool
    {
        return $this->multipleProductDiscountsPerLine;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
