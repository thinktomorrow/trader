<?php

declare(strict_types=1);

namespace Common\Domain\Adjusters;

interface Adjuster
{
    public function adjust(object $object): void;
}
