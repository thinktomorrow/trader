<?php

namespace Thinktomorrow\Trader\Common\Domain\References;

class DefaultReferenceValue implements ReferenceValue
{
    /** @var string */
    private $prefix;

    /** @var string */
    private $value;

    public function __construct(string $prefix = '')
    {
        $this->prefix = $prefix;

        $this->generate();
    }


    public function generate(): ReferenceValue
    {
        $this->value = time() . '-' .str_pad( (string) mt_rand(1,999),3,"0",STR_PAD_LEFT);

        return $this;
    }

    public function set(string $value): ReferenceValue
    {
        $this->value = $value;

        return $this;
    }

    public function get(): string
    {
        return $this->prefix . $this->value;
    }

    public function __toString()
    {
        return $this->get();
    }
}
