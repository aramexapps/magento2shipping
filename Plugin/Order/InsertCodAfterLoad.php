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
use Magento\Sales\Model\ResourceModel\Order\Collection as Collection;

/**
 * Functionality to add "COD" message on order page
 */
class InsertCodAfterLoad
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
     * @param object $subject Collection
     * @param array $orders Orders
     * @return object Orders
     */
    public function afterGetItems(Collection $subject, array $orders)
    {
        return array_map(function (Order $order) {
            return $this->codFunctionality->setExtensionFromData($order);
        }, $orders);
    }
}
