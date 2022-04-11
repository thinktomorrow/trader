<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\ProductOptions;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

interface VariantForProductOptionRepository
{
    public function getVariantsForProductOption(ProductId $productId): VariantForProductOptionCollection;
}
