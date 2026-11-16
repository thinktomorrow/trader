<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

class CreateSystemPromo
{
    private string $systemPromoId;

    protected array $data;

    /** @param list<string> $combinesWith */
    public function __construct(string $systemPromoId, protected array $combinesWith, private ?string $couponCode, private bool $multipleProductDiscountsPerLine, array $data)
    {
        $this->systemPromoId = $systemPromoId;
        $this->data = $data;
    }

    public function getSystemPromoId(): PromoId
    {
        return PromoId::fromString($this->systemPromoId);
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

    public function getCouponCode(): ?string
    {
        return $this->couponCode;
    }
}
