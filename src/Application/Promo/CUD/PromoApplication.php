<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountFactory;
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

        $promo = Promo::create($promoId, $command->getCouponCode(), $command->getStartAt(), $command->getEndAt(), $command->isCombinable());
        $promo->addData($command->getData());

        $this->promoRepository->save($promo);

        $this->eventDispatcher->dispatchAll($promo->releaseEvents());

        return $promoId;
    }

    public function createSystemPromo(CreateSystemPromo $command): PromoId
    {
        $promoId = $command->getSystemPromoId();

        $promo = Promo::create($promoId, null, null, null, $command->isCombinable());
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
        $promoId = $this->createSystemPromo(new CreateSystemPromo('system_sale_price', true, []));

        $this->updatePromo(new UpdatePromo(
            $promoId->get(),
            null,
            null,
            null,
            true,
            [
                [
                    'discountId' => SalePriceSystemDiscount::getMapKey(),
                    'key' => SalePriceSystemDiscount::getMapKey(),
                    'conditions' => [],
                    'data' => [],
                ],
            ],
            []
        ));
    }

    public function updatePromo(UpdatePromo $command): void
    {
        $promo = $this->promoRepository->find($command->getPromoId());

        $promo->updateCouponCode($command->getCouponCode());
        $promo->updateStartAt($command->getStartAt());
        $promo->updateEndAt($command->getEndAt());
        $promo->updateIsCombinable($command->isCombinable());
        $promo->addData($command->getData());

        $discounts = [];
        foreach ($command->getDiscounts() as $commandDiscount) {
            $discountId = $commandDiscount->getDiscountId() ?: $this->promoRepository->nextDiscountReference();

            // Conditions ...
            $conditionStates = [];
            foreach ($commandDiscount->getConditions() as $conditionPayload) {
                $conditionStates[] = [
                    'key' => $conditionPayload->getMapKey(),
                    'data' => json_encode($conditionPayload->getData()),
                ];
            }

            $discounts[] = $this->discountFactory->make(
                $commandDiscount->getMapKey(),
                [
                    'promo_id' => $promo->promoId->get(),
                    'discount_id' => $discountId->get(),
                    'data' => json_encode($commandDiscount->getData()),
                ],
                $promo->getMappedData(),
                $conditionStates
            );
        }

        $promo->updateDiscounts($discounts);

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
