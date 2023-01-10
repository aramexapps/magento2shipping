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
 * Class to get "Product Group"
 */
class Productgroup
{
    /**
     * Get array group of products
     *
     * @return array Array
     */
    public function toOptionArray()
    {
        $arr[] = ['value'=>'DOM', 'label'=>'Domestic'];
        $arr[] = ['value'=>'EXP', 'label'=>'International Express'];
        return $arr;
    }
}
