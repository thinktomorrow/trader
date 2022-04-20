<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\UpdateProduct;

use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;

class UpdateProductOptionValueItem
{
    private ?string $optionValueId;
    private array $data;

    public function __construct(?string $optionValueId, array $data)
    {
        $this->optionValueId = $optionValueId;
        $this->data = $data;
    }

    public function getOptionValueId(): ?OptionValueId
    {
        return $this->optionValueId ? OptionValueId::fromString($this->optionValueId) : null;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
