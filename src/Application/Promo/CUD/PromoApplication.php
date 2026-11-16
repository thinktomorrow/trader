<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Promo\CombinationPolicy;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\Discount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\SalePriceSystemDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Events\PromoDeleted;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;
use Thinktomorrow\Trader\TraderConfig;

class PromoApplication
{
    private TraderConfig $traderConfig;

    private EventDispatcher $eventDispatcher;

    private PromoRepository $promoRepository;

    private DiscountFactory $discountFactory;

    public function __construct(TraderConfig $traderConfig, EventDispatcher $eventDispatcher, PromoRepository $promoRepository, DiscountFactory $discountFactory)
    {
        $this->traderConfig = $traderConfig;
        $this->eventDispatcher = $eventDispatcher;
        $this->promoRepository = $promoRepository;
        $this->discountFactory = $discountFactory;
    }

    public function createPromo(CreatePromo $command): PromoId
    {
        $promoId = $this->promoRepository->nextReference();

        $promo = Promo::create($promoId, $command->getCouponCode(), $command->getStartAt(), $command->getEndAt(), new CombinationPolicy($command->getCombinesWith(), $command->allowsMultipleProductDiscountsPerLine()));
        $promo->addData($command->getData());

        $this->promoRepository->save($promo);

        $this->eventDispatcher->dispatchAll($promo->releaseEvents());

        return $promoId;
    }

    public function createSystemPromo(CreateSystemPromo $command): PromoId
    {
        $promoId = $command->getSystemPromoId();

        $promo = Promo::create($promoId, $command->getCouponCode(), null, null, new CombinationPolicy($command->getCombinesWith(), $command->allowsMultipleProductDiscountsPerLine()));
        $promo->addData($command->getData());

        $promo->setAsSystemPromo();

        $this->promoRepository->save($promo);

        $this->eventDispatcher->dispatchAll($promo->releaseEvents());

        return $promoId;
    }

    /**
     * The system promo that applies sale prices throughout the system.
     * Without this promo, sale prices would not be applied. This
     * allows to apply other discounts and ignore sale prices
     */
    public function createSalePriceSystemPromo(): void
    {
        $promoId = $this->createSystemPromo(new CreateSystemPromo('system_sale_price', DiscountableType::values(), null, false, []));

        $this->createPromoDiscount(new CreatePromoDiscount(
            $promoId->get(),
            SalePriceSystemDiscount::getMapKey(),
            [],
            [],
            null,
            SalePriceSystemDiscount::getMapKey(),
        ));
    }

    public function createPromoDiscount(CreatePromoDiscount $command): PromoDiscountId
    {
        $promo = $this->promoRepository->find($command->getPromoId());
        $discountId = $command->getDiscountId() ?? $this->promoRepository->nextPromoDiscountReference();

        $promo->addPromoDiscount($this->makeDiscount($promo, $discountId, $command));

        $this->promoRepository->save($promo);
        $this->eventDispatcher->dispatchAll($promo->releaseEvents());

        return $discountId;
    }

    public function updatePromoDiscount(UpdatePromoDiscount $command): void
    {
        $promo = $this->promoRepository->find($command->getPromoId());
        $currentDiscount = $promo->findPromoDiscount($command->getDiscountId());
        $discountableType = $command->getDiscountableType() ?? ($currentDiscount::getMapKey() === $command->getMapKey() ? $currentDiscount->getDiscountableType() : null);

        $promo->updatePromoDiscount($this->makeDiscount($promo, $command->getDiscountId(), $command, $discountableType));

        $this->promoRepository->save($promo);
        $this->eventDispatcher->dispatchAll($promo->releaseEvents());
    }

    public function deletePromoDiscount(DeletePromoDiscount $command): void
    {
        $promo = $this->promoRepository->find($command->getPromoId());
        $promo->deletePromoDiscount($command->getDiscountId());

        $this->promoRepository->save($promo);
        $this->eventDispatcher->dispatchAll($promo->releaseEvents());
    }

    private function makeDiscount(Promo $promo, PromoDiscountId $discountId, CreatePromoDiscount|UpdatePromoDiscount $command, ?DiscountableType $discountableType = null): Discount
    {
        $conditionStates = [];

        foreach ($command->getConditions() as $condition) {
            $conditionStates[] = [
                'key' => $condition->getMapKey(),
                'data' => json_encode($condition->getData()),
            ];
        }

        return $this->discountFactory->make(
            $command->getMapKey(),
            [
                'promo_id' => $promo->promoId->get(),
                'discount_id' => $discountId->get(),
                'discountable_type' => ($command->getDiscountableType() ?? $discountableType)?->value,
                'data' => json_encode($command->getData()),
            ],
            $promo->getMappedData(),
            $conditionStates,
        );
    }

    public function updatePromo(UpdatePromo $command): void
    {
        $promo = $this->promoRepository->find($command->getPromoId());

        $promo->updateCouponCode($command->getCouponCode());
        $promo->updateStartAt($command->getStartAt());
        $promo->updateEndAt($command->getEndAt());
        $promo->updateCombinationPolicy(new CombinationPolicy($command->getCombinesWith(), $command->allowsMultipleProductDiscountsPerLine()));
        $promo->addData($command->getData());

        $this->promoRepository->save($promo);

        $this->eventDispatcher->dispatchAll($promo->releaseEvents());
    }

    public function deletePromo(DeletePromo $command): void
    {
        $this->promoRepository->delete($command->getPromoId());

        $this->eventDispatcher->dispatchAll([
            new PromoDeleted($command->getPromoId()),
        ]);
    }
}
