<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Read;

interface CartLinePersonalisation
{
    public function getLabel(?string $locale = null): string;

    public function getType(): string;

    public function getValue();
}
