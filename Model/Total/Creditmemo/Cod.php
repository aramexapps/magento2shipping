<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.1.1
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Model\Total\Creditmemo;

/**
 * Class for Creditmemo total
 */
class Cod extends \Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal
{
    /**
     * Collect credit memo subtotal
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        parent::collect($creditmemo);
        $baseCod = $creditmemo->getOrder()->getExtensionAttributes()->getBaseAramexCashOnDelivery();
        $cod = $creditmemo->getOrder()->getExtensionAttributes()->getAramexCashOnDelivery();
        $creditmemo->setData('aramex_cash_on_delivery', $cod);
        $creditmemo->setData('base_aramex_cash_on_delivery', $baseCod);
        if (0 != round($cod, 2)) {
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $cod);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $cod);
        }

        return $this;
    }
}
