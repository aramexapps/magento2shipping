<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Block\Adminhtml\Order;

/**
 * Controller for "\Adminhtml\Aramex\Shipment.phtml"
 */
class View extends \Magento\Sales\Block\Adminhtml\Order\View
{
    /**
     * Order id
     *
     * @var string
     */
    public $order_id;

    /**
     * {@inheritdoc}
     */
    public function _construct()
    {
        $itemscount = 0;
        $totalWeight = 0;
        $this->order_id = $this->getOrder()->getId();
        $itemsv = $this->getOrder()->getAllVisibleItems();
        foreach ($itemsv as $itemvv) {
            if ((int)$itemvv->getQtyOrdered() > (int)$itemvv->getQtyShipped()) {
                $itemscount += (int)$itemvv->getQtyOrdered() - (int)$itemvv->getQtyShipped();
            }
            if ($itemvv->getWeight() != 0) {
                $weight = $itemvv->getWeight() * $itemvv->getQtyOrdered();
            } else {
                $weight = 0.5 * $itemvv->getQtyOrdered();
            }
            $totalWeight += $weight;
        }

        $this->getHistory($itemscount);

        parent::_construct();
    }
    
    /**
     * Get history list
     *
     * @param string $itemscount Quantity
     * @return void
     */
    private function getHistory($itemscount)
    {
        $history = [];
        $_order = $this->getOrder();
        if ($_order->getShipmentsCollection()->getSize()) {
            foreach ($_order->getShipmentsCollection() as $_shipment) {
                if ($_shipment->getCommentsCollection()->getSize()) {
                    foreach ($_shipment->getCommentsCollection() as $_comment) {
                        $history[] = $_comment->getComment();
                    }
                }
            }
        }

        $aramex_return_button = false;
        if (!empty($history)) {
            foreach ($history as $_history) {
                $awbno = strstr($_history, " - Order No", true);
                $awbno = trim($awbno, "Aramex Shipment Order AWB No. ");
                break;
            }
            if (isset($awbno)) {
                if ((int)$awbno) {
                    $aramex_return_button = true;
                }
            }
        }
        $this->getButtonText($_order, $aramex_return_button, $itemscount);
    }
    
    /**
     * Create button
     *
     * @param object $_order Order
     * @param boolean $aramex_return_button Return button indicator
     * @param string $itemscount Quantity
     * @return void
     */
    private function getButtonText($_order, $aramex_return_button, $itemscount)
    {
        if ($_order->canShip()) {
            $this->buttonList->add('create_aramex_shipment', [
                'label' => __('Prepare Aramex Shipment'),
                'class' => "itemscount_".$itemscount
            ]);
        } elseif (!$_order->canShip() && $aramex_return_button) {
            $this->buttonList->add('create_aramex_shipment', [
              'label' => __('Return Aramex Shipment'),
              'class' => "itemscount_".$itemscount
            ]);
        }
    }
}
