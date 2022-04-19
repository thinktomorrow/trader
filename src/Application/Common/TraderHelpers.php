<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Common;

class TraderHelpers
{
    public static function array_remove(array &$array, $key)
    {
        $value = $array[$key] ?? null;
        unset($array[$key]);

        return $value;
    }
}
