<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.1.1
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Plugin\Order;

use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;
use Magento\Sales\Block\Order\Totals;
use Magento\Quote\Api\Data\TotalsInterface;
use Aramex\Shipping\Model\Total\Cod;

/**
 * Functionality to add "COD" message on order page
 */
class ApplyCodToTotals
{
    /**
     * Functionality to add "COD" message on order page
     *
     * @param object $totals Totals
     * @param object $shippedOrder Order
     * @return object Order
     */

    public function afterGetOrder(Totals $totals, Order $shippedOrder)
    {
        if (empty($totals->getTotals())) {
            return $shippedOrder;
        }

        if (false !== $totals->getTotal('aramex_cash_on_delivery')) {
            return $shippedOrder;
        }

        $price = $shippedOrder->getExtensionAttributes()->getAramexCashOnDelivery();

        if ($price > 0) {
            $totals->addTotalBefore(new DataObject([
                'label' => __('Cash on Delivery'),
                'value' => $price,
                'code' => "aramex_cash_on_delivery",
            ]));
        }
        return $shippedOrder;
    }
}
