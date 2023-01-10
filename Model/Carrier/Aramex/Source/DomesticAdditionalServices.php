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
 * Domestic Additional Services
 */
class DomesticAdditionalServices
{
    /**
     * Gets array with  Domestic Additional Services
     *
     * @return array Domestic Additional Services
     */
    public function toOptionArray()
    {
        $arr[] = ['value'=>'AM10', 'label'=>'Morning delivery'];
        $arr[] = ['value'=>'CHST', 'label'=>'Chain Stores Delivery'];
        $arr[] = ['value'=>'CODS', 'label'=>'Cash On Delivery Service'];
        $arr[] = ['value'=>'COMM', 'label'=>'Commercial'];
        $arr[] = ['value'=>'CRDT', 'label'=>'Credit Card'];
        $arr[] = ['value'=>'DDP', 'label'=>'DDP - Delivery Duty Paid - For European Use'];
        $arr[] = ['value'=>'DDU', 'label'=>'DDU - Delivery Duty Unpaid - For the European Freight'];
        $arr[] = ['value'=>'EXW', 'label'=>'Not An Aramex Customer - For European Freight'];
        $arr[] = ['value'=>'INSR', 'label'=>'Insurance'];
        $arr[] = ['value'=>'RTRN', 'label'=>'Return'];
        $arr[] = ['value'=>'SPCL', 'label'=>'Special Services'];
        return $arr;
    }
}
