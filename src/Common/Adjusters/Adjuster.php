<?php

namespace Optiphar\Cart\Adjusters;

interface Adjuster
{
    public function adjust(object $object): void;
}
