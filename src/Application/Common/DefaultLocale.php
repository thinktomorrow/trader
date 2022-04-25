<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Common;

use Thinktomorrow\Trader\Domain\Common\Locale;

class DefaultLocale
{
    private static Locale $defaultLocale;

    public static function get(): Locale
    {
        return static::$defaultLocale;
    }

    public static function set(Locale $locale): void
    {
        static::$defaultLocale = $locale;
    }
}
