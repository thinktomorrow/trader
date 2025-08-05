<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\UpdateVariant;

use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

class UpdateVariantOptionValues
{
    private string $productId;
    private string $variantId;
    private array $optionValueIds;

    public function __construct(string $productId, string $variantId, array $optionValueIds)
    {
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->optionValueIds = $optionValueIds;
    }

    public function getProductId(): ProductId
    {
        return ProductId::fromString($this->productId);
    }

    public function getVariantId(): VariantId
    {
        return VariantId::fromString($this->variantId);
    }

    /**
     * @return OptionValueId[]
     */
    public function getOptionValueIds(): array
    {
        return array_map(fn ($value_id) => OptionValueId::fromString($value_id), $this->optionValueIds);
    }
}
