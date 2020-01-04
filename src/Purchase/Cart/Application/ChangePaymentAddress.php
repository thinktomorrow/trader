<?php

namespace Optiphar\Cart\Application;

use Optiphar\Areas\CountryTranslation;
use Optiphar\Cart\Http\DbCurrentCartSource;
use Optiphar\Areas\CountryRepositoryContract;

class ChangePaymentAddress
{
    /** @var DbCurrentCartSource */
    private $currentCart;

    /** @var CountryRepositoryContract */
    private $countryRepository;

    public function __construct(DbCurrentCartSource $currentCart, CountryRepositoryContract $countryRepository)
    {
        $this->currentCart = $currentCart;
        $this->countryRepository = $countryRepository;
    }

    public function handle(ChangePaymentAddressCommand $command)
    {
        $cart = $this->currentCart->get();

        // Prep the country translations and set 'country' as key for the address translations
        $countryTranslations = $this->countryRepository->findByCountryId($command->countryId)->translations
            ->map(function(CountryTranslation $trans){
                $trans->country = $trans->name;
                return $trans;
            })
            ->keyBy('locale')
            ->map->only(['country'])
            ->toArray();

        $adjustedCartPayment = $cart->payment()->adjustAddress([
            'street'     => $command->street,
            'number'     => $command->number,
            'bus'        => $command->bus,
            'postal'     => $command->postal,
            'city'       => $command->city,
            'countryid'  => $command->countryId,
            'salutation' => $command->salutation,
            'firstname'  => $command->firstname,
            'lastname'   => $command->lastname,
            'vatid'      => $command->vat,
            'valid_vat'  => $command->validVat,
            'company'    => $command->company,
            'translations' => $countryTranslations,
        ]);

        $cart->replacePayment($adjustedCartPayment);

        $this->currentCart->save($cart);
    }
}
