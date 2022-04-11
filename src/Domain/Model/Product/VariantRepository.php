<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

interface VariantRepository
{
    public function save(Variant $variant): void;

    public function find(VariantId $variantId): Variant;

    public function delete(VariantId $variantId): void;

    public function nextReference(): VariantId;
}
