<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.1.1
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Plugin\Order\OrderRepository;

use Aramex\Shipping\Model\Order\CodFunctionality;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Apply Cod Before Save
 */
class ApplyCodBeforeSave
{
    /**
     * @var CodFunctionality
     */
    private $extensionManagement;

    public function __construct(CodFunctionality $extensionManagement)
    {
        $this->extensionManagement = $extensionManagement;
    }

    public function beforeSave(OrderRepositoryInterface $subject, Order $order): array
    {

        return [$this->extensionManagement->setDataFromExtension($order)];
    }
}
