<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\VariantForCart;

use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

interface VariantForCart
{
    public static function fromMappedData(array $state, array $personalisations): static;

    public function getVariantId(): VariantId;

    public function getProductId(): ProductId;

    public function getState(): VariantState;

    public function getUnitPrice(): VariantUnitPrice;

    public function getSalePrice(): VariantSalePrice;

    public function getTitle(?string $locale = null): string;

    /** @return Personalisation[] */
    public function getPersonalisations(): array;
}
