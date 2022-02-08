<?php

namespace Common\Domain\References;

interface ReferenceValue
{
    public function generate(): self;

    public function set(string $reference): self;

    public function get(): string;

    public function __toString();
}
