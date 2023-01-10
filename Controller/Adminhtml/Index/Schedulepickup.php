<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Controller\Adminhtml\Index;

/**
 * Controller for "Schedulepickup' functionality
 */
class Schedulepickup extends \Magento\Backend\App\Action
{
    /**
     * Object of \Magento\Framework\App\Config\ScopeConfigInterface
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
     /**
      * Object of \Magento\Framework\App\Config\ScopeConfigInterface
      * @var \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader
      */
    private $shipmentLoader;
    /**
     * Object of \Magento\Framework\App\Config\ScopeConfigInterface
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    /**
     * Object of \Magento\Framework\App\Config\ScopeConfigInterface
     * @var \Aramex\Shipping\Helper\Data
     */
    private $helper;
    /**
     * Object of \Magento\Framework\App\Config\ScopeConfigInterface
     * @var \Magento\Sales\Model\Order
     */
    private $order;
    /**
     * Object of \Magento\Sales\Model\Order\Shipment
     * @var \Magento\Sales\Model\Order\Shipment
     */
    
    private $shipment;
    /**
     * @var \Magento\Framework\Webapi\Soap\ClientFactory
     */
    private $soapClientFactory;
    /**
     * Constructor
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Aramex\Shipping\Helper\Data $helper
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Aramex\Shipping\Helper\Data $helper,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory,
        \Magento\Sales\Model\Order\Shipment $shipment
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->shipmentLoader = $shipmentLoader;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->order = $order;
        $this->shipment = $shipment;
        $this->soapClientFactory = $soapClientFactory;
        parent::__construct($context);
    }
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $post = $this->getRequest()->getPost();
        $response = [];
        $clientInfo = $this->helper->getClientInfo();
        try {
            $post = $post['pickup'];
            $_order = $this->order->loadByIncrementId($post['reference']);
            $pickupDate = strtotime($post['date']);
            $readyTimeH = $post['ready_hour'];
            $readyTimeM = $post['ready_minute'];
            $readyTime = mktime(
                ($readyTimeH - 2),
                $readyTimeM,
                0,
                date("m", $pickupDate),
                date("d", $pickupDate),
                date("Y", $pickupDate)
            );

            $closingTimeH = $post['latest_hour'];
            $closingTimeM = $post['latest_minute'];
            $closingTime = mktime(
                ($closingTimeH - 2),
                $closingTimeM,
                0,
                date("m", $pickupDate),
                date("d", $pickupDate),
                date("Y", $pickupDate)
            );
            $params = [
                'ClientInfo' => $clientInfo,
                'Transaction' => [
                    'Reference1' => $post['reference']
                ],
                'Pickup' => [
                    'PickupContact' => [
                        'PersonName' => $post['contact'],
                        'CompanyName' => $post['company'],
                        'PhoneNumber1' => $post['phone'],
                        'PhoneNumber1Ext' => $post['ext'],
                        'CellPhone' => $post['mobile'],
                        'EmailAddress' => $post['email']
                    ],
                    'PickupAddress' => [
                        'Line1' => $post['address'],
                        'City' => $post['city'],
                        'StateOrProvinceCode' => $post['state'],
                        'PostCode' => $post['zip'],
                        'CountryCode' => $post['country']
                    ],
                    'PickupLocation' => $post['location'],
                    'PickupDate' => $readyTime,
                    'ReadyTime' => $readyTime,
                    'LastPickupTime' => $closingTime,
                    'ClosingTime' => $closingTime,
                    'Comments' => $post['comments'],
                    'Reference1' => $post['reference'],
                    'Reference2' => '',
                    'Vehicle' => $post['vehicle'],
                    'Shipments' => [
                        'Shipment' => []
                    ],
                    'PickupItems' => [
                        'PickupItemDetail' => [
                            'ProductGroup' => $post['product_group'],
                            'ProductType' => $post['product_type'],
                            'Payment' => $post['payment_type'],
                            'NumberOfShipments' => $post['no_shipments'],
                            'NumberOfPieces' => $post['no_pieces'],
                            'ShipmentWeight' => ['Value' => $post['text_weight'], 'Unit' => $post['weight_unit']],
                        ],
                    ],
                    'Status' => $post['status']
                ]
            ];
            $baseUrl = $this->helper->getWsdlPath();
            //SOAP object
            $soapClient = $this->soapClientFactory->create($baseUrl .
                'shipping.wsdl', ['version' => SOAP_1_1,'trace' => 1, 'keep_alive' => false]);
            try {
                $results = $soapClient->CreatePickup($params);
                if ($results->HasErrors) {
                    if (is_array($results->Notifications->Notification)) {
                        $error = "";
                        foreach ($results->Notifications->Notification as $notify_error) {
                            $error.='Aramex: ' . $notify_error->Code . ' - ' . $notify_error->Message . "<br>";
                        }
                        $response['error'] = $error;
                        $response['type'] = 'error';
                    } else {
                        if (str_contains($results->Notifications->Notification->Message, 'Duplicate collection already exists')) { 
                            
                            preg_match('#\((.*?)\)#', $results->Notifications->Notification->Message, $match);

                            $pickup_no = $match[1];
                            $history_pickup = false;
                            foreach ($_order->getStatusHistoryCollection() as $status) {
                                if ($status->getComment()) {
                                    if (str_contains($status->getComment(), $pickup_no)) {
                                        $history_pickup = true;
                                    }
                                }
                            }
            
                            if($history_pickup==0){
                                $notify = false;
                                $comment = "Pickup reference number ( <strong>" . $pickup_no . "</strong> ).";
                                
                                if($_order->getStatus()){
                                    $history = $_order->addStatusHistoryComment($comment, $_order->getStatus())
                                        ->setIsCustomerNotified($notify);
                                    $history->save();
                                }

                                if($_order->getId()){
                                    $shipmentId = null;
                                    $shipment = $this->shipment->getCollection()
                                                    ->addFieldToFilter("order_id", $_order->getId())->load();

                                    if ($shipment->getSize() > 0) {
                                        foreach ($shipment as $_shipment) {
                                            $shipmentId = $_shipment->getId();
                                            break;
                                        }
                                    }
                                    if ($shipmentId != null) {
                                        $data = [['comment_text' => $comment]];
                                        $this->shipmentLoader->setOrderId($post['order_id']);
                                        $this->shipmentLoader->setShipmentId(null);
                                        $this->shipmentLoader->setShipment($data);
                                        $this->shipmentLoader->setTracking(null);
                                        $shipment = $this->shipmentLoader->load();
                                        if (!empty($data['comment_text'])) {
                                            $shipment->addComment(
                                                $data['comment_text'],
                                                isset($data['comment_customer_notify']),
                                                isset($data['is_visible_on_front'])
                                            );

                                            $shipment->setCustomerNote($data['comment_text']);
                                            $shipment->setCustomerNoteNotify(isset($data['comment_customer_notify']));
                                        }
                                    }
                                }
                            }

                            $response['type'] = 'success';
                            $amount = "<p class='amount'>Pickup reference number ( <strong>" . $pickup_no .
                                "</strong> ).</p>";
                            $response['html'] = $amount;

                        }else{

                            $response['error'] = 'Aramex: ' . $results->Notifications->Notification->Code . ' - ' .
                            $results->Notifications->Notification->Message;
                            $response['type'] = 'error';

                        }
                    }
                } else {
                    $notify = false;
                    $comment = "Pickup reference number ( <strong>" . $results->ProcessedPickup->ID . "</strong> ).";
                    
                    if($_order->getStatus()){
                        $history = $_order->addStatusHistoryComment($comment, $_order->getStatus())
                                ->setIsCustomerNotified($notify);
                        $history->save();
                    }

                    if($_order->getId()){
                        $shipmentId = null;
                        $shipment = $this->shipment->getCollection()
                                        ->addFieldToFilter("order_id", $_order->getId())->load();

                        if ($shipment->getSize() > 0) {
                            foreach ($shipment as $_shipment) {
                                $shipmentId = $_shipment->getId();
                                break;
                            }
                        }
                        if ($shipmentId != null) {
                            $data = [['comment_text' => $comment]];
                            $this->shipmentLoader->setOrderId($post['order_id']);
                            $this->shipmentLoader->setShipmentId(null);
                            $this->shipmentLoader->setShipment($data);
                            $this->shipmentLoader->setTracking(null);
                            $shipment = $this->shipmentLoader->load();
                            if (!empty($data['comment_text'])) {
                                $shipment->addComment(
                                    $data['comment_text'],
                                    isset($data['comment_customer_notify']),
                                    isset($data['is_visible_on_front'])
                                );

                                $shipment->setCustomerNote($data['comment_text']);
                                $shipment->setCustomerNoteNotify(isset($data['comment_customer_notify']));
                            }
                        }
                    }
                    $response['type'] = 'success';
                    $amount = "<p class='amount'>Pickup reference number ( <strong>" . $results->ProcessedPickup->ID .
                        "</strong> ).</p>";
                    $response['html'] = $amount;
                }
            } catch (\Exception $e) {
                $response['type'] = 'error';
                $response['error'] = $e->getMessage();
            }
        } catch (\Exception $e) {
            $response['type'] = 'error';
            $response['error'] = $e->getMessage();
        }
        return  $this->resultJsonFactory->create()->setData($response);
    }
}
