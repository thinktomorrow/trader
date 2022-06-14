<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\VariantForCart;

use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;

interface VariantForCart
{
    public static function fromMappedData(array $state): static;

    public function getVariantId(): VariantId;
    public function getState(): VariantState;
    public function getSalePrice(): VariantSalePrice;
    public function getTitle(): string;
}
