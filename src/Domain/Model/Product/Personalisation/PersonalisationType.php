<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Personalisation;

class PersonalisationType
{
    const TEXT = 'text';
    const IMAGE = 'image';

    private string $type;

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function fromString(string $type): static
    {
        return new static($type);
    }

    public function get(): string
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return $this->type;
    }

    public function equals($other): bool
    {
        return get_class($other) === get_class($this)
            && (string)$this === (string)$other;
    }
}
