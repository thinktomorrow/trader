<?php


namespace Thinktomorrow\Trader\Common\Helpers;


trait HandlesType
{
    /**
     * Unique string identifier of this condition.
     * @var string
     */
    protected $type;

    public function getType(): string
    {
        return $this->type;
    }

    protected function guessType(): string
    {
        $shortName = (new \ReflectionClass($this))->getShortName();

        return static::snakeCase($shortName);
    }

    protected static function isAssocArray($array)
    {
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }

    private static function snakeCase($value, $delimiter = '_')
    {
        if (ctype_lower($value)) return $value;

        $value = preg_replace('/\s+/u', '', ucwords($value));
        return mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
    }
}