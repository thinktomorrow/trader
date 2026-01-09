<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;

interface MerchantOrderDiscount
{
    public static function fromMappedData(array $state, array $cartState): static;

    public function getDiscountId(): string;

    public function getDiscountPrice(): DiscountPrice;

    public function getFormattedDiscountPriceExcl(): string;

    public function getPercentage(): string;

    public function getTitle(): ?string;

    public function getDescription(): ?string;

    public function getData(string $key, $default = null): mixed;
}
