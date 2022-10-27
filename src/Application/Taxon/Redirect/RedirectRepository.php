<?php

namespace Thinktomorrow\Trader\Application\Taxon\Redirect;

use Thinktomorrow\Trader\Domain\Common\Locale;

interface RedirectRepository
{
    public function find(Locale $locale, string $from): ?Redirect;

    public function getAllTo(Locale $locale, string $to): array;

    public function save(Redirect $redirect): void;

    public function delete(Redirect $redirect): void;
}
