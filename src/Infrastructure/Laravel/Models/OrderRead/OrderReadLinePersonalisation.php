<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Thinktomorrow\Trader\Application\Common\RendersData;

abstract class OrderReadLinePersonalisation
{
    use RendersData;

    protected string $line_id;
    protected string $personalisation_id;
    protected string $personalisation_type;
    protected $value;
    protected array $data;

    final public function __construct()
    {
    }

    public static function fromMappedData(array $state, array $lineState): static
    {
        $personalisation = new static();

        $personalisation->line_id = $lineState['line_id'];
        $personalisation->personalisation_id = $state['personalisation_id'];
        $personalisation->personalisation_type = $state['personalisation_type'];
        $personalisation->value = $state['value'];
        $personalisation->data = json_decode($state['data'], true);

        return $personalisation;
    }

    public function getLineId(): string
    {
        return $this->line_id;
    }

    public function getLabel(): string
    {
        return $this->data('title', null, '');
    }

    public function getType(): string
    {
        return $this->personalisation_type;
    }

    public function getValue()
    {
        return $this->value;
    }
}