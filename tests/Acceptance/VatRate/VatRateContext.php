<?php
declare(strict_types=1);

namespace Tests\Acceptance\VatRate;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\VatRate\CreateBaseRate;
use Thinktomorrow\Trader\Application\VatRate\CreateVatRate;
use Thinktomorrow\Trader\Application\VatRate\VatRateApplication;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRateId;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVatRateRepository;

class VatRateContext extends TestCase
{
    protected VatRateApplication $vatRateApplication;
    protected InMemoryVatRateRepository $vatRateRepository;
    protected EventDispatcherSpy $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vatRateApplication = new VatRateApplication(
            $this->eventDispatcher = new EventDispatcherSpy(),
            $this->vatRateRepository = new InMemoryVatRateRepository(),
        );
    }

    public function tearDown(): void
    {
        $this->vatRateRepository->clear();
    }

    protected function createBaseRateStub(): array
    {
        $originVatRateId = $this->vatRateApplication->createVatRate(new CreateVatRate(
            'BE', '21', ['foo' => 'bar']
        ));

        $this->vatRateRepository->setNextReference('zzz-123');
        $targetVatRateId = $this->vatRateApplication->createVatRate(new CreateVatRate(
            'NL', '20', ['foo' => 'baz']
        ));

        return [
            'originVatRateId' => $originVatRateId,
            'targetVatRateId' => $targetVatRateId,
            'baseRateId' => $this->vatRateApplication->createBaseRate(new CreateBaseRate($originVatRateId->get(), $targetVatRateId->get()))
        ];
    }
}
