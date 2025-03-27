<?php

namespace Tests\Infrastructure\Vies;

use SoapFault;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\VatRate\VatNumberValidation;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatNumber;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatNumberValidationState;
use Thinktomorrow\Trader\Infrastructure\Vies\ViesClient;
use Thinktomorrow\Trader\Infrastructure\Vies\ViesVatNumberValidator;

class ViesVatNumberValidatorTest extends TestCase
{
    private ViesVatNumberValidator $validator;
    private $viesClientMock;

    protected function setUp(): void
    {
        // Mock de ViesClient zodat we geen echte API calls maken
        $this->viesClientMock = $this->createMock(ViesClient::class);

        // Injecteer de mock client via dependency injection
        $this->validator = new ViesVatNumberValidator($this->viesClientMock);
    }

    public function test_it_validates_a_valid_vat_number()
    {
        $vatNumber = VatNumber::make(CountryId::fromString('BE'), '0412192313');

        $this->viesClientMock
            ->method('check')
            ->willReturn((object)['valid' => true]);

        $result = $this->validator->validate($vatNumber);

        $this->assertInstanceOf(VatNumberValidation::class, $result);
        $this->assertEquals(VatNumberValidationState::valid, $result->state);
    }

    public function test_it_returns_invalid_for_a_wrong_vat_number()
    {
        $vatNumber = VatNumber::make(CountryId::fromString('BE'), '0000000000');

        $this->viesClientMock
            ->method('check')
            ->willReturn((object)['valid' => false]);

        $result = $this->validator->validate($vatNumber);

        $this->assertEquals(VatNumberValidationState::invalid, $result->state);
    }

    public function test_it_handles_invalid_input_exception()
    {
        $vatNumber = VatNumber::make(CountryId::fromString('BE'), '012345678');

        $this->viesClientMock
            ->method('check')
            ->willThrowException(new SoapFault('INVALID_INPUT', 'INVALID_INPUT'));

        $result = $this->validator->validate($vatNumber);

        $this->assertEquals(VatNumberValidationState::invalid, $result->state);
        $this->assertArrayHasKey('message', $result->data);
        $this->assertEquals('Invalid VAT number', $result->data['message']);
    }

    public function test_it_handles_vies_service_unavailable()
    {
        $vatNumber = VatNumber::make(CountryId::fromString('BE'), '0412192313');

        $this->viesClientMock
            ->method('check')
            ->willThrowException(new SoapFault('SERVICE_UNAVAILABLE', 'VIES service unavailable'));

        $result = $this->validator->validate($vatNumber);

        $this->assertEquals(VatNumberValidationState::service_error, $result->state);
        $this->assertArrayHasKey('message', $result->data);
        $this->assertStringContainsString('VIES service is currently unavailable', $result->data['message']);
    }
}
