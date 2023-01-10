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
 * Domestic methods
 */
class Domesticmethods
{
    /**
     * Gets array with Domestic methods
     *
     * @return array Domestic methods
     */
    public function toOptionArray()
    {
        $arr[] = ['value'=>'BLK', 'label'=>'Special: Bulk Mail Delivery'];
        $arr[] = ['value'=>'BLT', 'label'=>'Domestic - Bullet Delivery'];
        $arr[] = ['value'=>'CDA', 'label'=>'Special Delivery'];
        $arr[] = ['value'=>'CDS', 'label'=>'Special: Credit Cards Delivery'];
        $arr[] = ['value'=>'CGO', 'label'=>'Air Cargo (India)'];
        $arr[] = ['value'=>'COM', 'label'=>'Special: Cheque Collection'];
        $arr[] = ['value'=>'DEC', 'label'=>'Special: Invoice Delivery'];
        $arr[] = ['value'=>'EMD', 'label'=>'Early Morning delivery'];
        $arr[] = ['value'=>'FIX', 'label'=>'Special: Bank Branches Run'];
        $arr[] = ['value'=>'LGS', 'label'=>'Logistic Shipment'];
        $arr[] = ['value'=>'OND', 'label'=>'Overnight (Document)'];
        $arr[] = ['value'=>'ONP', 'label'=>'Overnight (Parcel)'];
        $arr[] = ['value'=>'P24', 'label'=>'Road Freight 24 hours service'];
        $arr[] = ['value'=>'P48', 'label'=>'Road Freight 48 hours service'];
        $arr[] = ['value'=>'PEC', 'label'=>'Economy Delivery'];
        $arr[] = ['value'=>'PEX', 'label'=>'Road Express'];
        $arr[] = ['value'=>'SFC', 'label'=>'Surface  Cargo (India)'];
        $arr[] = ['value'=>'SMD', 'label'=>'Same Day (Document)'];
        $arr[] = ['value'=>'SMP', 'label'=>'Same Day (Parcel)'];
        $arr[] = ['value'=>'SDD', 'label'=>'Same Day Delivery'];
        $arr[] = ['value'=>'HVY', 'label'=>'Heavy (20kgs and more)'];
        $arr[] = ['value'=>'SPD', 'label'=>'Special: Legal Branches Mail Service'];
        $arr[] = ['value'=>'SPL', 'label'=>'Special : Legal Notifications Delivery'];
        
        return $arr;
    }
    
    /**
     * Transfer object to array
     *
     * @return array Array
     */
    public function toKeyArray()
    {
        $result  = [];
        $options = $this->toOptionArray();
        foreach ($options as $option) {
            $result[$option['value']] = $option['label'];
        }
        return $result;
    }
}
