<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

interface MerchantOrderLinePersonalisation
{
    public function getLabel(?string $locale = null): string;

    public function getType(): string;

    public function getValue();
}
