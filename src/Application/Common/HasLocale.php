<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Common;

use Thinktomorrow\Trader\Domain\Common\Locale;

trait HasLocale
{
    private ?Locale $locale = null;

    public function setLocale(Locale $locale): void
    {
        $this->locale = $locale;
    }
}