<?php

namespace Purchase\Cart\Application;

use Optiphar\Areas\CountryTranslation;
use Optiphar\Cart\Http\DbCurrentCartSource;
use Optiphar\Areas\CountryRepositoryContract;

class ChangeShippingAddress
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

    public function handle(ChangeShippingAddressCommand $command)
    {
        $cart = $this->currentCart->get();

        $countryTranslations = [];

        if($command->countryId) {
            // Prep the country translations and set 'country' as key for the address translations
            $countryTranslations = $this->countryRepository->findByCountryId($command->countryId)->translations
                ->map(function(CountryTranslation $trans){
                    $trans->country = $trans->name;
                    return $trans;
                })
                ->keyBy('locale')
                ->map->only(['country'])
                ->toArray();
        }



        $adjustedCartShipping = $cart->shipping()->adjustAddress([
            'street'     => $command->street,
            'number'     => $command->number,
            'bus'        => $command->bus,
            'postal'     => $command->postal,
            'city'       => $command->city,
            'countryid'  => $command->countryId,
            'salutation' => $command->salutation,
            'firstname'  => $command->firstname,
            'lastname'   => $command->lastname,
            'company'    => $command->company,
            'translations' => $countryTranslations,
        ]);

        $cart->replaceShipping($adjustedCartShipping);

        $this->currentCart->save($cart);
    }
}
