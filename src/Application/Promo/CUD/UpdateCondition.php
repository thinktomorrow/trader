<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

class UpdateCondition
{
    private string $key;
    private array $data;

    public function __construct(string $key, array $data)
    {
        $this->key = $key;
        $this->data = $data;
    }

    public function getMapKey(): string
    {
        return $this->key;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
