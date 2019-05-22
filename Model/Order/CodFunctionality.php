<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.1.1
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Model\Order;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Model\Order;
use Aramex\Shipping\Model\Total\Cod;
use Magento\Quote\Model\Quote\Address;

    /**
     * Class Cod Functionality
     */
class CodFunctionality
{
    /**
     * @var OrderExtensionFactory
     */
    private $orderExtensionFactory;
    /**
     * @param \Magento\Sales\Api\Data\OrderExtensionFactory $orderExtensionFactory
     */
    public function __construct(OrderExtensionFactory $orderExtensionFactory)
    {
        $this->orderExtensionFactory = $orderExtensionFactory;
    }
    
    /**
     * Sets Extension From Data
     *
     * @param object $order Order
     * @return object
     */
    public function setExtensionFromData(Order $order)
    {

        $extra = $this->getExtra($order);
        $extra->setAramexCashOnDelivery($order->getData('aramex_cash_on_delivery'));
        $extra->setBaseAramexCashOnDelivery($order->getData('base_aramex_cash_on_delivery'));
        return $order;
    }
    
    /**
     * Sets Extension From Data
     *
     * @param object $order Order
     * @param object $address Address
     * @return object
     */
    public function setExtensionFromAddressData(Order $order, Address $address)
    {
        $extra = $this->getExtra($order);
        $extra->setAramexCashOnDelivery($address->getData('aramex_cash_on_delivery'));
        $extra->setBaseAramexCashOnDelivery($address->getData('base_aramex_cash_on_delivery'));
        return $order;
    }
    
    /**
     * Sets Data From Extension
     *
     * @param object $order Order
     * @return object
     */
    public function setDataFromExtension(Order $order)
    {
        $extra = $this->getExtra($order);
        $order->setData('aramex_cash_on_delivery', $extra->getAramexCashOnDelivery());
        $order->setData('base_aramex_cash_on_delivery', $extra->getBaseAramexCashOnDelivery());
        return $order;
    }
    /**
     * Gets Extra
     *
     * @param object $order Order
     * @return object
     */
    private function getExtra(Order $order)
    {
        $extra = $order->getExtensionAttributes();
        if ($extra === null) {
            $extra = $this->orderExtensionFactory->create();
            $order->setExtensionAttributes($extra);
            return $extra;
        }
        return $extra;
    }
}
