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
 * International Additional Services
 */
class InternationalAdditionalServices
{
    /**
     * Gets array with International Additional Services
     *
     * @return array International Additional Services
     */
    public function toOptionArray()
    {
        $arr[] = ['value'=>'AM10', 'label'=>'Morning delivery'];
        $arr[] = ['value'=>'CODS', 'label'=>'Cash On Delivery'];
        $arr[] = ['value'=>'CSTM', 'label'=>'CSTM'];
        $arr[] = ['value'=>'EUCO', 'label'=>'NULL'];
        $arr[] = ['value'=>'FDAC', 'label'=>'FDAC'];
        $arr[] = ['value'=>'FRDM', 'label'=>'FRDM'];
        $arr[] = ['value'=>'INSR', 'label'=>'Insurance'];
        $arr[] = ['value'=>'NOON', 'label'=>'Noon Delivery'];
        $arr[] = ['value'=>'ODDS', 'label'=>'Over Size'];
        $arr[] = ['value'=>'RTRN', 'label'=>'RTRN'];
        $arr[] = ['value'=>'SIGR', 'label'=>'Signature Required'];
        $arr[] = ['value'=>'SPCL', 'label'=>'Special Services'];
        return $arr;
    }
}
