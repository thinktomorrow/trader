<?php

declare(strict_types=1);

namespace Optiphar\Cart\Application;

use App\Http\Requests\Request;
use Optiphar\Areas\DeliveryAddress;
use Optiphar\Invoices\Vat\VatNumberStatus;

class ChangePaymentAddressCommand
{
    public $street;
    public $number;
    public $bus;
    public $city;
    public $postal;
    public $countryId;
    public $salutation;
    public $firstname;
    public $lastname;
    public $company;
    public $vat;
    public $validVat;

    public function __construct(string $street, string $number, ?string $bus, string $city, string $postal, string $countryId, string $salutation, string $firstname, string $lastname, ?string $company, ?string $vat, bool $validVat)
    {
        $this->street = $street;
        $this->number = $number;
        $this->bus = $bus;
        $this->city = $city;
        $this->postal = $postal;
        $this->countryId = $countryId;
        $this->salutation = $salutation;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->company = $company;
        $this->vat = $vat;
        $this->validVat = $validVat;
    }

    public static function payloadFromRequest(Request $request, array $overrides = []): array
    {
        $payload = array_merge([
            'street' => $request->get('street'),
            'number' => $request->get('number'),
            'bus' => $request->get('bus'),
            'city' => $request->get('city'),
            'postal' => DeliveryAddress::sanitizePostalCode($request->get('postal')),
            'countryid' => $request->get('countryid'),
            'salutation' => $request->get('salutation', 'mr'),
            'firstname' => $request->get('firstname'),
            'lastname' => $request->get('lastname'),
            'company' => $request->get('company'),
            'vatid' => $request->get('vatid'),
            'valid_vat' => $request->get('valid_vat'),
        ], $overrides);

        $payload['valid_vat'] = VatNumberStatus::fromKey($payload['valid_vat'])->getBoolean();

        return array_values($payload);
    }

    public function __set($name, $value)
    {
        throw new \Exception('A command class - once instantiated - is immutable and cannot be altered.');
    }
}
