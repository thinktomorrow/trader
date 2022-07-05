<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

class CreateCouponPromo
{
    private string $couponCode;
    private ?string $startAt;
    private ?string $endAt;
    private bool $isCombinable;
    private array $data;

    public function __construct(string $couponCode, ?string $startAt, ?string $endAt, bool $isCombinable, array $data)
    {
        $this->couponCode = $couponCode;
        $this->startAt = $startAt;
        $this->endAt = $endAt;
        $this->isCombinable = $isCombinable;
        $this->data = $data;
    }

    public function getCouponCode(): string
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

    public function getData(): array
    {
        return $this->data;
    }
}
