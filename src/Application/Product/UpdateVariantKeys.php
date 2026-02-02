<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantKey\VariantKey;
use Thinktomorrow\Trader\Domain\Model\Product\VariantKey\VariantKeyId;

class UpdateVariantKeys
{
    private string $productId;
    private string $variantId;
    private array $variantKeys;

    public function __construct(string $productId, string $variantId, array $variantKeys)
    {
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->variantKeys = $variantKeys;
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
     * @return VariantKey[]
     */
    public function getVariantKeys(): array
    {
        $result = [];

        foreach ($this->variantKeys as $locale => $variantKey) {
            $result[] = VariantKey::create($this->getVariantId(), VariantKeyId::fromString($variantKey), Locale::fromString($locale));
        }

        return $result;
    }
}
