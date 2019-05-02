<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Model\Total;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Store\Model\ScopeInterface;

/**
 * Class for Cod calculation
 */
class Cod extends AbstractTotal
{
    /**
     * @var Checkout Session
     */
    private $checkoutSession;
    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface
        $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        if (count($shippingAssignment->getItems()) == 0) {
            return $this;
        }

        $cashOnDeliveryFee = $this->getFee($quote);
        $total->setData('aramex_cash_on_delivery', $cashOnDeliveryFee);
        $total->setData('base_aramex_cash_on_delivery', $cashOnDeliveryFee);
        $total->setTotalAmount('aramex_cash_on_delivery', $cashOnDeliveryFee);
        $total->setBaseTotalAmount('base_aramex_cash_on_delivery', $cashOnDeliveryFee);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function fetch(Quote $quote, Total $total)
    {
        return [
            'code' => 'aramex_cash_on_delivery',
            'title' => 'Cash On Delivery',
            'value' => $this->getFee($quote)
        ];
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return __('Cash On Delivery');
    }

    /**
     * Get Cod Fee
     *
     * @param object $quote Quote
     * @return mixed
     */
    private function getFee(Quote $quote)
    {
        if ($quote->getPayment()->getMethod() !== Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
            return null;
        }
        if (!empty($this->checkoutSession->getAramexShippingData())) {
            $aramexShipppigMethod = ltrim($quote->getShippingAddress()->getShippingMethod(), "aramex_");
            foreach ($this->checkoutSession->getAramexShippingData() as $value) {
                if (array_key_exists($aramexShipppigMethod, $value)) {
                    if (isset($value[$aramexShipppigMethod]['cod'])) {
                        return $value[$aramexShipppigMethod]['cod'];
                    } else {
                        return null;
                    }
                }

            }
        }
            return null;
    }
}
