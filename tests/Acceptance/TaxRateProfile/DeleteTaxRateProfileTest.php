<?php
declare(strict_types=1);

namespace Tests\Acceptance\TaxRateProfile;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\TaxRateProfile\CreateTaxRateDouble;
use Thinktomorrow\Trader\Application\TaxRateProfile\CreateTaxRateProfile;
use Thinktomorrow\Trader\Application\TaxRateProfile\DeleteTaxRateDouble;
use Thinktomorrow\Trader\Application\TaxRateProfile\DeleteTaxRateProfile;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\Events\TaxRateDoubleDeleted;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\Events\TaxRateProfileDeleted;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\Exceptions\CouldNotFindTaxRateProfile;

class DeleteTaxRateProfileTest extends TaxRateProfileContext
{
    use TestHelpers;

    public function test_it_can_delete_a_profile()
    {
        $taxRateProfileId = $this->taxRateProfileApplication->createTaxRateProfile(new CreateTaxRateProfile(
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $this->taxRateProfileApplication->deleteTaxRateProfile(new DeleteTaxRateProfile($taxRateProfileId->get()));

        $this->assertEquals([
            new TaxRateProfileDeleted($taxRateProfileId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        $this->expectException(CouldNotFindTaxRateProfile::class);
        $this->taxRateProfileRepository->find($taxRateProfileId);
    }

    public function test_it_can_delete_a_double()
    {
        $taxRateProfileId = $this->taxRateProfileApplication->createTaxRateProfile(new CreateTaxRateProfile(
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $taxRateDoubleId = $this->taxRateProfileApplication->createTaxRateDouble(new CreateTaxRateDouble($taxRateProfileId->get(), '21', '10'));

        $this->taxRateProfileApplication->deleteTaxRateDouble(new DeleteTaxRateDouble($taxRateProfileId->get(), $taxRateDoubleId->get()));

        $this->assertEquals([
            new TaxRateDoubleDeleted($taxRateProfileId, $taxRateDoubleId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        $taxRateProfile = $this->taxRateProfileRepository->find($taxRateProfileId);
        $this->assertCount(0, $taxRateProfile->getTaxRateDoubles());
    }
}
