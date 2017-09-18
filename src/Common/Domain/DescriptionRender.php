<?php

namespace Thinktomorrow\Trader\Common\Domain;

interface DescriptionRender
{
    public function description(Description $description);

    public function locale($locale = null): string;
}