<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Plugin\Order;

use Magento\Sales\Model\Order;
use Aramex\Shipping\Model\Order\CodFunctionality;

/**
 * Functionality to add "COD" message on order page
 */
class ApplyCodAfterLoad
{
    /**
     * @var CodFunctionality
     */
    private $codFunctionality;

    public function __construct(CodFunctionality $codFunctionality)
    {
        $this->codFunctionality = $codFunctionality;
    }

    /**
     * Functionality to add "COD" message on order page
     *
     * @param object $subject Order
     * @param object $returnedOrder Order
     * @return object Order
     */
    public function afterLoad(Order $subject, Order $returnedOrder)
    {
        return $this->codFunctionality->setExtensionFromData($returnedOrder);
    }
}
