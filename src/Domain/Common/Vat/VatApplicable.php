<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Vat;

interface VatApplicable
{
    public function getVatPercentage(): VatPercentage;

    public function getVatApplicableTotal(): VatApplicableTotal;
}
