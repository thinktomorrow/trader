<?php

declare(strict_types=1);

namespace Tests\Acceptance\VatRate;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\VatRate\CreateBaseRate;
use Thinktomorrow\Trader\Application\VatRate\CreateVatRate;

class VatRateContext extends TestCase
{
    protected function createBaseRateStub(): array
    {
        $originVatRateId = $this->catalogContext->apps()->vatRateApplication()->createVatRate(new CreateVatRate(
            'BE',
            '21',
            ['foo' => 'bar']
        ));

        $this->catalogContext->repos()->vatRateRepository()->setNextReference('zzz-123');
        $targetVatRateId = $this->catalogContext->apps()->vatRateApplication()->createVatRate(new CreateVatRate(
            'NL',
            '20',
            ['foo' => 'baz']
        ));

        return [
            'originVatRateId' => $originVatRateId,
            'targetVatRateId' => $targetVatRateId,
            'baseRateId' => $this->catalogContext->apps()->vatRateApplication()->createBaseRate(new CreateBaseRate($originVatRateId->get(), $targetVatRateId->get())),
        ];
    }
}
