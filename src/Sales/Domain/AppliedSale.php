<?php

namespace Thinktomorrow\Trader\Sales\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;

class AppliedSale
{
    private $id;
    private $type;
    private $saleAmount;
    private $salePercentage;
    private $data;

    public function __construct(SaleId $id, string $type, Money $saleAmount, Percentage $salePercentage, array $data = [])
    {
        $this->id = $id;
        $this->type = $type;
        $this->saleAmount = $saleAmount;
        $this->salePercentage = $salePercentage;
        $this->data = $data;
    }

    public function saleId(): SaleId
    {
        return $this->id;
    }

    public function saleType(): string
    {
        return $this->type;
    }

    public function saleAmount(): Money
    {
        return $this->saleAmount;
    }

    public function salePercentage(): Percentage
    {
        return $this->salePercentage;
    }

    public function data($key = null, $default = null)
    {
        if(is_null($key)) return $this->data;

        if (!is_null($key) && isset($this->data[$key])) {
            return $this->data[$key];
        }

        return $this->handleNestedKeysViaDotSyntax($key, $default);
    }

    /**
     * collects from nested array via dot syntax.
     * Taken from the mkiha GetModelValue functionality
     */
    private function handleNestedKeysViaDotSyntax($key, $default = null)
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
                    : (array)$value;
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
