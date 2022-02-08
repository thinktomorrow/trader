<?php

namespace Optiphar\Cart\Application;

class ChangeShippingAddressCommand
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

    public function __construct(string $street, string $number, ?string $bus, string $city, string $postal, string $countryId, string $salutation, string $firstname, string $lastname, ?string $company)
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
    }

    public function __set($name, $value)
    {
        throw new \Exception('A command class - once instantiated - is immutable and cannot be altered.');
    }
}
