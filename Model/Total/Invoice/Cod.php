<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Model\Total\Invoice;

/**
 * Class for invoice total
 */
class Cod extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal
{
    /**
     * Collect invoice subtotal
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        parent::collect($invoice);
        $baseCod = $invoice->getOrder()->getExtensionAttributes()->getBaseAramexCashOnDelivery();
        $cod = $invoice->getOrder()->getExtensionAttributes()->getAramexCashOnDelivery();
        $invoice->setData('base_aramex_cash_on_delivery', $baseCod);
        $invoice->setData('aramex_cash_on_delivery', $cod);

        if (0 != round($cod, 2)) {
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $cod);
            $invoice->setGrandTotal($invoice->getGrandTotal() + $cod);

        }
        return $this;
    }
}
