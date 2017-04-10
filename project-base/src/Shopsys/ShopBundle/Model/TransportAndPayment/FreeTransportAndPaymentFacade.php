<?php

namespace Shopsys\ShopBundle\Model\TransportAndPayment;

use Shopsys\ShopBundle\Model\Pricing\PricingSetting;

class FreeTransportAndPaymentFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Pricing\PricingSetting
     */
    private $pricingSetting;

    public function __construct(PricingSetting $pricingSetting)
    {
        $this->pricingSetting = $pricingSetting;
    }

    /**
     * @param int $domainId
     * @return bool
     */
    public function isActive($domainId)
    {
        $freeTransportAndPaymentPriceLimit = $this->getFreeTransportAndPaymentPriceLimitOnDomain($domainId);
        if ($freeTransportAndPaymentPriceLimit === null) {
            return false;
        }

        return true;
    }

    /**
     * @param string $productsPriceWithVat
     * @param int $domainId
     * @return bool
     */
    public function isFree($productsPriceWithVat, $domainId)
    {
        $freeTransportAndPaymentPriceLimit = $this->getFreeTransportAndPaymentPriceLimitOnDomain($domainId);
        if ($freeTransportAndPaymentPriceLimit === null) {
            return false;
        }

        return $productsPriceWithVat >= $freeTransportAndPaymentPriceLimit;
    }

    /**
     * @param string $productsPriceWithVat
     * @param int $domainId
     * @return int
     */
    public function getRemainingPriceWithVat($productsPriceWithVat, $domainId)
    {
        if (!$this->isFree($productsPriceWithVat, $domainId)) {
            return $this->getFreeTransportAndPaymentPriceLimitOnDomain($domainId) - $productsPriceWithVat;
        }

        return 0;
    }

    /**
     * @param int $domainId
     * @return string
     */
    private function getFreeTransportAndPaymentPriceLimitOnDomain($domainId)
    {
        return $this->pricingSetting->getFreeTransportAndPaymentPriceLimit($domainId);
    }
}