<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Options\Ports;

use Thinktomorrow\Trader\Catalog\Options\Domain\Option as OptionContract;

class Option implements OptionContract
{
    private string $id;
    private string $productGroupId;
    private string $optionTypeId;
    private array $values;
    private array $labels;

    public function __construct(string $id, string $productGroupId, string $optionTypeId, array $values, array $labels)
    {
        $this->id = $id;
        $this->productGroupId = $productGroupId;
        $this->optionTypeId = $optionTypeId;
        $this->values = $values;
        $this->labels = $labels;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProductGroupId(): string
    {
        return $this->productGroupId;
    }

    public function getOptionTypeId(): string
    {
        return $this->optionTypeId;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getValue(string $index): ?string
    {
        if (! isset($this->values[$index])) {
            return null;
        }

        return $this->values[$index];
    }

    public function getLabel(string $index): ?string
    {
        if (! isset($this->labels[$index])) {
            return null;
        }

        return $this->labels[$index];
    }
}
