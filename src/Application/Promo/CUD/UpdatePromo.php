<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

class UpdatePromo
{
    private string $promoId;
    private ?string $couponCode;
    private ?string $startAt;
    private ?string $endAt;
    private bool $isCombinable;
    private array $discounts;
    private array $data;

    public function __construct(string $promoId, ?string $couponCode, ?string $startAt, ?string $endAt, bool $isCombinable, array $discounts, array $data)
    {
        $this->promoId = $promoId;
        $this->couponCode = $couponCode;
        $this->startAt = $startAt;
        $this->endAt = $endAt;
        $this->isCombinable = $isCombinable;
        $this->discounts = $discounts;
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

    public function isCombinable(): bool
    {
        return $this->isCombinable;
    }

    /** @return UpdateDiscount[] */
    public function getDiscounts(): array
    {
        return array_map(fn (array $discountPayload) => new UpdateDiscount(...$discountPayload), $this->discounts);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
