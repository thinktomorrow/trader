<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Events;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantKey\VariantKeyId;

class VariantKeyUpdated
{
    public function __construct(
        public readonly VariantId    $variantId,
        public readonly Locale       $locale,
        public readonly VariantKeyId $formerVariantKeyId,
        public readonly VariantKeyId $newVariantKeyId,
    )
    {
    }
}
