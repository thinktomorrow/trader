<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Common;

class DataRenderer
{
    private static \Closure $resolver;

    public static function setResolver(\Closure $resolver)
    {
        static::$resolver = $resolver;
    }

    public static function get(array $data, string $key, string $language = null, $default = null)
    {
        return call_user_func_array(static::$resolver, [
            $data,
            $key,
            $language,
            $default,
        ]);
    }
}
