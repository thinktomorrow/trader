<?php

namespace Tests\Infrastructure\Vies;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Infrastructure\Vies\ViesClient;

class ViesClientTest extends TestCase
{
    private ViesClient $viesClient;

    protected function setUp(): void
    {
        $this->viesClient = ViesClient::createDefault();
    }

    public function test_it_can_validate_a_valid_vat_number()
    {
        $response = $this->viesClient->check('BE', '0832456968'); // Valid BE VAT number

        $this->assertIsObject($response);
        $this->assertTrue($response->valid);
        $this->assertEquals('BE', $response->countryCode);
        $this->assertEquals('0832456968', $response->vatNumber);
    }

    public function test_it_returns_invalid_for_non_existing_vat_number()
    {
        $response = $this->viesClient->check('BE', '0000000000');

        $this->assertIsObject($response);
        $this->assertFalse($response->valid);
        $this->assertEquals('BE', $response->countryCode);
        $this->assertEquals('0000000000', $response->vatNumber);
    }

    public function test_it_throws_exception_for_invalid_country_code()
    {
        $this->expectException(\SoapFault::class);

        $this->viesClient->check('XX', '0412192313'); // 'XX' is een ongeldig landcode
    }
}
