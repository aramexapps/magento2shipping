<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Model\Carrier\Aramex\Source;

/**
 * Class to get "Defaultpaymenttype"
 */
class Defaultpaymenttype
{
    /**
     * Get array group of payment type
     *
     * @return array Array
     */
    public function toOptionArray()
    {
        $arr[] = ['value'=>'P', 'label'=>'Prepaid'];
		$arr[] = ['value'=>'C', 'label'=>'Collect'];
		$arr[] = ['value'=>'3', 'label'=>'Third Party'];
        return $arr;
    }
}
