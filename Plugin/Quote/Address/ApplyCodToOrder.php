<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.1.1
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Plugin\Quote\Address;

use Magento\Quote\Model\Quote\Address\ToOrder as ToOrder;
use Magento\Quote\Model\Quote\Address as Address;
use Aramex\Shipping\Model\Order\CodFunctionality;

/**
 * Apply Cod To Order
 */
class ApplyCodToOrder
{
    /**
     * @var CodFunctionality
     */
    private $codFunctionality;

     /**
      * Constructor
      *
      * @param Aramex\Shipping\Model\Order\CodFunctionality $codFunctionality
      */
    public function __construct(CodFunctionality $codFunctionality)
    {
        $this->codFunctionality = $codFunctionality;
    }

    /**
     * Functionality to add "COD" message on order page
     *
     * @param object $subject Order
     * @param object $proceed Closure
     * @param object $Address Address
     * @return object Order
     */
    public function aroundConvert(
        ToOrder $subject,
        \Closure $proceed,
        Address $Address,
        array $data = []
    ) {
        return $this->codFunctionality->setExtensionFromAddressData($proceed($Address, $data), $Address);
    }
}
