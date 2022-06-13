<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Event;

use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

final class OptionValuesUpdated
{
    public readonly ProductId $productId;
    public readonly OptionId $optionId;

    public function __construct(ProductId $productId, OptionId $optionId)
    {
        $this->productId = $productId;
        $this->optionId = $optionId;
    }
}
