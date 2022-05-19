<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\GetProductOptions;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;

class ProductOption
{
    use HasLocale;
    use RendersData;

    public readonly OptionId $optionId;
    public readonly OptionValueId $optionValueId;
    private array $data;
    private ?string $url = null;
    private bool $isActive = false;
    private array $optionData;

    public function __construct(OptionId $optionId, OptionValueId $optionValueId, array $data, array $optionData)
    {
        $this->optionId = $optionId;
        $this->optionValueId = $optionValueId;
        $this->data = $data;
        $this->optionData = $optionData;
    }

    public function getLabel(): string
    {
        return $this->data('label', null, '');
    }

    public function getValue(): string
    {
        return $this->data('value', null, '');
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function markActive(bool $isActive = true): void
    {
        $this->isActive = $isActive;
    }

    public static function fromMappedData(array $state): static
    {
        return new static(
            OptionId::fromString($state['option_id']),
            OptionValueId::fromString($state['option_value_id']),
            json_decode($state['data'], true),
            json_decode($state['option_data'], true),
        );
    }
}
