<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;

class CreatePromo
{
    protected ?string $couponCode;

    protected ?string $startAt;

    protected ?string $endAt;

    protected array $data;

    /** @var list<string> */
    protected array $combinesWith;

    /** @param list<string> $combinesWith */
    public function __construct(?string $couponCode, ?string $startAt, ?string $endAt, array $combinesWith, protected bool $multipleProductDiscountsPerLine, array $data)
    {
        $this->couponCode = $couponCode;
        $this->startAt = $startAt;
        $this->endAt = $endAt;
        $this->data = $data;
        $this->combinesWith = $combinesWith;
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
