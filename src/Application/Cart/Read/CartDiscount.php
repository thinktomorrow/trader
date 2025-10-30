<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Read;

use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;

interface CartDiscount
{
    public static function fromMappedData(array $state, array $cartState): static;

    public function getDiscountId(): string;

    public function getDiscountTotal(): DiscountTotal;

    public function getPrice(): string;

    public function getPercentage(): string;

    public function includeTax(bool $includeTax = true): void;

    public function getTitle(): ?string;

    public function getDescription(): ?string;
}
