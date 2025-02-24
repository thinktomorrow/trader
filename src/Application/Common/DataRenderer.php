<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Common;

class DataRenderer
{
    private static \Closure $dataResolver;

    public static function setDataResolver(\Closure $dataResolver)
    {
        static::$dataResolver = $dataResolver;
    }

    public static function get(array $data, string $key, ?string $language = null, $default = null)
    {
        return call_user_func_array(static::$dataResolver, [
            $data,
            $key,
            $language,
            $default,
        ]);
    }
}
