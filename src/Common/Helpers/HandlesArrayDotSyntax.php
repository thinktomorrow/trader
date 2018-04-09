<?php

namespace Thinktomorrow\Trader\Common\Helpers;

trait HandlesArrayDotSyntax
{
    /**
     * collects from nested array via dot syntax.
     * Taken from the mkiha GetModelValue functionality.
     *
     * Note: this assumes a data array property which contains the full data array
     */
    protected function handlesArrayDotSyntax($key, $default = null)
    {
        $keys = explode('.', $key);

        if (($firstKey = array_shift($keys)) && !isset($this->data[$firstKey])) {
            return $default;
        }
        $value = $this->data[$firstKey];

        foreach ($keys as $nestedKey) {
            // Normalize to array
            if (is_object($value)) {
                $value = method_exists($value, 'toArray')
                    ? $value->toArray()
                    : (array) $value;
            }

            if (!isset($value[$nestedKey])) {
                $value = $default;
                break;
            }

            $value = $value[$nestedKey];
        }

        return $value;
    }
}
