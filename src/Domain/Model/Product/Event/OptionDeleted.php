<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Event;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;

final class OptionDeleted
{
    public readonly ProductId $productId;
    public readonly OptionId $optionId;

    public function __construct(ProductId $productId, OptionId $optionId)
    {
        $this->productId = $productId;
        $this->optionId = $optionId;
    }
}
