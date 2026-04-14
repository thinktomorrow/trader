<?php

namespace Tests\Infrastructure\Vies;

use SoapFault;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Infrastructure\Vies\ViesClient;

class ViesClientTest extends TestCase
{
    private ViesClient $viesClient;

    /**
     * @var list<string>
     */
    private array $transientFaultMessages = [
        'TIMEOUT',
        'MS_MAX_CONCURRENT_REQ',
        'GLOBAL_MAX_CONCURRENT_REQ',
        'SERVER_BUSY',
        'SERVICE_UNAVAILABLE',
    ];

    protected function setUp(): void
    {
        $this->viesClient = ViesClient::createDefault();
    }

    public function test_it_can_validate_a_valid_vat_number()
    {
        try {
            $response = $this->viesClient->check('BE', '0832456968'); // Valid BE VAT number
        } catch (SoapFault $e) {
            $this->skipOnTransientSoapFault($e);

            throw $e;
        }

        $this->assertIsObject($response);
        $this->assertTrue($response->valid);
        $this->assertEquals('BE', $response->countryCode);
        $this->assertEquals('0832456968', $response->vatNumber);
    }

    public function test_it_returns_invalid_for_non_existing_vat_number()
    {
        try {
            $response = $this->viesClient->check('BE', '0000000000');
        } catch (SoapFault $e) {
            $this->skipOnTransientSoapFault($e);

            throw $e;
        }

        $this->assertIsObject($response);
        $this->assertFalse($response->valid);
        $this->assertEquals('BE', $response->countryCode);
        $this->assertEquals('0000000000', $response->vatNumber);
    }

    public function test_it_throws_exception_for_invalid_country_code()
    {
        $this->expectException(SoapFault::class);

        try {
            $this->viesClient->check('XX', '0412192313'); // 'XX' is een ongeldig landcode
        } catch (SoapFault $e) {
            $this->skipOnTransientSoapFault($e);

            throw $e;
        }
    }

    private function skipOnTransientSoapFault(SoapFault $e): void
    {
        if (! in_array($e->getMessage(), $this->transientFaultMessages, true)) {
            return;
        }

        $this->markTestSkipped('VIES temporarily unavailable: ['.$e->getMessage().']');
    }
}
