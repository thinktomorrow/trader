<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Read;

use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;

interface CartDiscount
{
    public static function fromMappedData(array $state, array $cartState): static;

    public function getDiscountId(): string;

    public function getDiscountPrice(): DiscountPrice;

    public function getFormattedDiscountPriceExcl(): string;

    public function getPercentage(): string;

    public function getTitle(): ?string;

    public function getDescription(): ?string;

    public function isCouponCodeBased(): bool;

    public function getCouponCode(): ?string;
}
