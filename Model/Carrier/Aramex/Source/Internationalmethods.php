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
 * Internationals methods
 */
class Internationalmethods
{
    /**
     * Get array with Internationals methods
     *
     * @return array Internationals methods
     */
    public function toOptionArray()
    {
        $arr[] = ['value'=>'DPX', 'label'=>'Value Express Parcels'];
        $arr[] = ['value'=>'EDX', 'label'=>'Economy Document Express'];
        $arr[] = ['value'=>'EPX', 'label'=>'Economy Parcel Express'];
        $arr[] = ['value'=>'GDX', 'label'=>'Ground Document Express'];
        $arr[] = ['value'=>'GPX', 'label'=>'Ground Parcel Express'];
        $arr[] = ['value'=>'IBD', 'label'=>'International defered'];
        $arr[] = ['value'=>'PDX', 'label'=>'Priority Document Express'];
        $arr[] = ['value'=>'PLX', 'label'=>'Priority Letter Express (<.5 kg Docs)'];
        $arr[] = ['value'=>'PPX', 'label'=>'Priority Parcel Express'];
        
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
