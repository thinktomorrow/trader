<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderReadLine;

class DefaultCartLine extends OrderReadLine implements CartLine
{
    public function getUrl(): string
    {
        return $this->getVariantId();
    }

    public function getVariantId(): string
    {
        return parent::getVariantId();
    }
}
