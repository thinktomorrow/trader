<?php

namespace Tests\Unit\Model;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\Exceptions\InvalidVatNumber;
use Thinktomorrow\Trader\Domain\Model\VatRate\Exceptions\VatNumberCountryMismatch;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatNumber;

class VatNumberTest extends TestCase
{
    public function test_it_creates_a_vat_number_from_string()
    {
        $vatNumber = VatNumber::make(CountryId::fromString('BE'), 'BE0123456789');

        $this->assertInstanceOf(VatNumber::class, $vatNumber);
        $this->assertEquals('BE', $vatNumber->getCountryCode());
        $this->assertEquals('0123456789', $vatNumber->getNumber());
        $this->assertEquals('BE0123456789', $vatNumber->get());
    }

    public function test_it_creates_a_vat_number_without_a_prefixed_country_code()
    {
        $vatNumber = VatNumber::make(CountryId::fromString('NL'), '123456789B01');

        $this->assertEquals('NL', $vatNumber->getCountryCode());
        $this->assertEquals('123456789B01', $vatNumber->getNumber());
        $this->assertEquals('NL123456789B01', (string)$vatNumber);
    }

    public function test_it_throws_an_exception_for_invalid_country_code()
    {
        $this->expectException(InvalidVatNumber::class);
        $this->expectExceptionMessage('Invalid country code [XYZ]');

        VatNumber::make(CountryId::fromString('XYZ'), '0123456789');
    }

    public function test_it_throws_an_exception_for_too_short_vat_number()
    {
        $this->expectException(InvalidVatNumber::class);
        $this->expectExceptionMessage('Invalid vat number [123456]');

        VatNumber::make(CountryId::fromString('BE'), '123456');
    }

    public function test_it_throws_an_exception_when_country_code_in_number_does_not_match()
    {
        $this->expectException(VatNumberCountryMismatch::class);
        $this->expectExceptionMessage('Invalid vat number [FR0123456789]. Included country code [FR] does not match given country code [BE]');

        VatNumber::make(CountryId::fromString('BE'), 'FR0123456789');
    }

    public function test_it_removes_non_alphanumeric_characters()
    {
        $vatNumber = VatNumber::make(CountryId::fromString('DE'), 'DE 123-456.789,00');

        $this->assertEquals('DE12345678900', (string)$vatNumber);
    }

    public function test_it_compares_two_vat_numbers_correctly()
    {
        $vatNumber1 = VatNumber::make(CountryId::fromString('IT'), 'IT12345678901');
        $vatNumber2 = VatNumber::make(CountryId::fromString('IT'), '12345678901');
        $vatNumber3 = VatNumber::make(CountryId::fromString('FR'), 'FR987654321');

        $this->assertTrue($vatNumber1->equals($vatNumber2));
        $this->assertFalse($vatNumber1->equals($vatNumber3));
    }

    public function test_it_converts_to_string_correctly()
    {
        $vatNumber = VatNumber::make(CountryId::fromString('ES'), 'ESX12345678');

        $this->assertEquals('ESX12345678', (string)$vatNumber);
    }

    public function test_it_correctly_finds_an_included_country_code()
    {
        $reflection = new \ReflectionClass(VatNumber::class);
        $method = $reflection->getMethod('findIncludedCountryCode');
        $method->setAccessible(true);

        $this->assertEquals('BE', $method->invoke(null, 'BE0123456789'));
        $this->assertEquals(null, $method->invoke(null, '123456789B01'));
    }
}
