<?php

namespace Thinktomorrow\Trader\Common\Domain;

class Description
{
    private $key;

    private $values = [];

    public function __construct(string $key, array $values)
    {
        $this->key = $key;
        $this->values = $values;
    }

    public function key(): string
    {
        return $this->key;
    }

    /**
     * Values to be used to fill the language placeholders
     * @return array
     */
    public function values(): array
    {
        return $this->values;
    }

    public function __toString()
    {
        return $this->key();
    }
}