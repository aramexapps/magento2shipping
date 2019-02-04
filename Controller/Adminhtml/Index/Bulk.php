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

use Magento\Framework\Controller\ResultFactory;

/**
 * Controller of "Bulk Aramex Shipment" functionality
 */
class Bulk extends \Magento\Backend\App\Action
{
    /**
     * @var string "email" path
     */
    const XML_PATH_TRANS_IDENTITY_EMAIL = 'trans_email/ident_general/email';
    /**
     * @var string "name" path
     */
    const XML_PATH_TRANS_IDENTITY_NAME = 'trans_email/ident_general/name';
    /**
     * @var string "shipment_template" path
     */
    const XML_PATH_SHIPMENT_EMAIL_TEMPLATE = 'aramex/template/shipment_template';
    /**
     * @var string "copy_to" path
     */
    const XML_PATH_SHIPMENT_EMAIL_COPY_TO = 'aramex/template/copy_to';
    /**
     * @var string "copy_method" path
     */
    const XML_PATH_SHIPMENT_EMAIL_COPY_METHOD = 'aramex/template/copy_method';

    /**
     * Object of \Magento\Framework\View\Result\PageFactory
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     * Object of \Magento\Framework\App\Config\ScopeConfigInterface
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Object of \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader
     * @var \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader
     */
    private $shipmentLoader;

    /**
     * Object of \Magento\Framework\Mail\Template\TransportBuilder
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * Object of \Magento\Framework\Controller\Result\JsonFactory
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Object of \Magento\Framework\DB\Transaction
     * @var \Magento\Framework\DB\Transaction
     */
    private $transaction;
    /**
     * Object of \Aramex\Shipping\Helper\Data
     * @var \Aramex\Shipping\Helper\Data
     */
    private $helper;
    /**
     * Object of \Magento\Sales\Model\Order
     * @var \Magento\Sales\Model\Order
     */
    private $order;
    /**
     * @var \Magento\Framework\Webapi\Soap\ClientFactory
     */
    private $soapClientFactory;
    /**
     * @var \Magento\Sales\Model\Order\Shipment\Track
     */
    private $tracking;
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    private $orderInterface;
    /**
     * Constructor
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory resultPageFactory
     * @param \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Aramex\Shipping\Helper\Data $helper
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment\Track
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Magento\Framework\Registry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Aramex\Shipping\Helper\Data $helper,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory,
        \Magento\Sales\Model\Order\Shipment\Track $tracking,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \Magento\Framework\Registry $registry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->shipmentLoader = $shipmentLoader;
        $this->transportBuilder = $transportBuilder;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->order = $order;
        $this->transaction = $transaction;
        $this->tracking = $tracking;
        $this->soapClientFactory = $soapClientFactory;
        $this->registry = $registry;
        $this->orderInterface = $orderInterface;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $post_out = $this->getRequest()->getPost();
        $post = [];
        $params = $this->unserializeForm($post_out['str']);
        $orders = [];
        $post['aramex_shipment_shipper_country'] = $this->scopeConfig->getValue(
            'aramex/settings/account_country_code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
       
        //check "pending" status
        $orders = $this->getOrdersWithPendingStatus($post_out, $post);
 
        //domestic metods must be first
        if (!empty($orders)) {
            $responce = "";
            foreach ($orders as $key => $orderItem) {
                $post['aramex_shipment_original_reference'] = (int) $orderItem['order_id'];
                $order = $this->order->loadByIncrementId((int)$orderItem['order_id']);
                $isShipped = false;
                $itemsv = $order->getAllVisibleItems();
                $totalWeight = 0;
                foreach ($itemsv as $itemvv) {
                    $weight = $this->getTotalWeight($itemvv);
                    $totalWeight += $weight;
                    if ($itemvv->getQtyOrdered() == $itemvv->getQtyShipped()) {
                        $isShipped = true;
                    }
                    //quontity
                    $_qty = abs($itemvv->getQtyOrdered() - $itemvv->getQtyShipped());
                    if ($_qty == 0 and $isShipped) {
                        $_qty = (int)$itemvv->getQtyShipped();
                    }

                    $post[$itemvv->getId()] = (string) $_qty;
                }
             
                $post['aramex_items'] = $this->getTotalItems($itemsv, $isShipped);
                $post['order_weight'] = (string) $totalWeight;
                $post['aramex_shipment_shipper_reference'] = $order->getIncrementId();
                $post['aramex_shipment_info_billing_account'] = 1;
                $post['aramex_shipment_shipper_account'] = $this->scopeConfig->
                    getValue(
                        'aramex/settings/account_number',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );
                $post['aramex_shipment_shipper_street'] = $this->scopeConfig->getValue(
                    'aramex/shipperdetail/address',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $post['aramex_shipment_shipper_city'] = $this->scopeConfig->getValue(
                    'aramex/shipperdetail/city',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $post['aramex_shipment_shipper_state'] = $this->scopeConfig->getValue(
                    'aramex/shipperdetail/state',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $post['aramex_shipment_shipper_postal'] = $this->scopeConfig->
                    getValue(
                        'aramex/shipperdetail/postalcode',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );
                $post['aramex_shipment_shipper_name'] = $this->scopeConfig->getValue(
                    'aramex/shipperdetail/name',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $post['aramex_shipment_shipper_company'] = $this->scopeConfig->getValue(
                    'aramex/shipperdetail/company',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $post['aramex_shipment_shipper_phone'] = $this->scopeConfig->getValue(
                    'aramex/shipperdetail/phone',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $post['aramex_shipment_shipper_email'] = $this->scopeConfig->getValue(
                    'aramex/shipperdetail/email',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );

                //shipper parameters
                $post['aramex_shipment_receiver_reference'] = $order->getIncrementId();
                $shipping = $order->getShippingAddress();
                $post['aramex_shipment_receiver_street'] = ($shipping) ? $shipping->getData('street') : '';
                $post['aramex_shipment_receiver_city'] = ($shipping) ? $shipping->getData('city') : '';
                $post['aramex_shipment_receiver_postal'] = ($shipping) ? $shipping->getData('postcode') : '';
                $post['aramex_shipment_receiver_country'] = ($shipping) ? $shipping->getData('country_id') : '';
                $post['aramex_shipment_receiver_name'] = ($shipping) ? $shipping->getName() : '';

                //Contact Info
                $post['aramex_shipment_receiver_name'] = ($shipping) ? $shipping->getName() : '';
                $company_name = isset($billing) ? $billing->getData('company') : '';
                $company_name = ($company_name) ? $company_name : '';
                $company_name = (empty($company_name) and $shipping) ? $shipping->getName() : $company_name;
                $company_name = ($shipping) ? $shipping->getData('company') : '';

                $post['aramex_shipment_receiver_company'] = (!empty($company_name))? $company_name :
                    $post['aramex_shipment_receiver_name'];
                $post['aramex_shipment_receiver_phone'] = ($shipping) ? $shipping->getData('telephone') : '';
                $post['aramex_shipment_receiver_email'] =  $order->getData('customer_email');
                // Other Main Shipment Parameters
                $post['aramex_shipment_info_reference'] = $order->getIncrementId();
                $post['aramex_shipment_info_foreignhawb'] = '';
                $post['aramex_shipment_info_comment'] = '';
                $post['weight_unit'] = 'KG';

                if ($orderItem['method'] == 'DOM') {
                    $post['aramex_shipment_info_product_group'] = $orderItem['method'];
                    $post['aramex_shipment_info_product_type'] = ($params['aramex_shipment_info_product_type_dom']) ?
                        $params['aramex_shipment_info_product_type_dom'] : "";
                    $post['aramex_shipment_info_payment_type'] = ($params['aramex_shipment_info_payment_type_dom']) ?
                        $params['aramex_shipment_info_payment_type_dom'] : "";
                    $post['aramex_shipment_info_payment_option'] = "";
                    $post['aramex_shipment_info_service_type'] = ($params['aramex_shipment_info_service_type_dom']) ?
                        $params['aramex_shipment_info_service_type_dom'] : "";
                    $post['aramex_shipment_currency_code'] = ($params['aramex_shipment_currency_code_dom']) ?
                        $params['aramex_shipment_currency_code_dom'] : "";
                    $post['aramex_shipment_info_custom_amount'] =  "";
                } else {
                    $post['aramex_shipment_info_product_group'] = $orderItem['method'];
                    $post['aramex_shipment_info_product_type'] = ($params['aramex_shipment_info_product_type']) ?
                        $params['aramex_shipment_info_product_type'] : "";
                    $post['aramex_shipment_info_payment_type'] = ($params['aramex_shipment_info_payment_type']) ?
                        $params['aramex_shipment_info_payment_type'] : "";
                    $post['aramex_shipment_info_payment_option'] = ($params['aramex_shipment_info_payment_option']) ?
                        $params['aramex_shipment_info_payment_option'] : "";
                    $post['aramex_shipment_info_service_type'] = ($params['aramex_shipment_info_service_type']) ?
                        $params['aramex_shipment_info_service_type'] : "";
                    $post['aramex_shipment_currency_code'] = ($params['aramex_shipment_currency_code']) ?
                        $params['aramex_shipment_currency_code'] : "";
                    $post['aramex_shipment_info_custom_amount'] = ($params['aramex_shipment_info_custom_amount']) ?
                        $params['aramex_shipment_info_custom_amount'] : "";
                }
                $aramex_shipment_description =  $this->getShipmentDescription($order);
                $post['aramex_shipment_description'] = $aramex_shipment_description;
                $post['aramex_shipment_info_cod_amount'] = ($order->getPayment()->getMethodInstance()->
                    getCode() != 'ccsave') ? (string) round($order->getData('grand_total'), 2) : '';
                $post['aramex_return_shipment_creation_date'] = "create";
                $post['aramex_shipment_referer'] = 0;

                $replay = $this->postAction($orderItem['method'], $post);
                if ($replay[1] == "DOM") {
                    $method = "Domestic Product Group";
                } else {
                    $method = "International Product Group";
                }
                
                if ($replay[2] == "error") {
                    $responce .= "<p class='aramex_red'>" . $replay[0] . " - " .
                        $orderItem['order_id'] . ' not created. (' . $method . ')</p>';
                    break;
                } else {
                    $responce .= "<p class='aramex_green'> Aramex Shipment Number: " .
                        $orderItem['order_id'] . ' has been created.(' . $method . ')</p>';
                }
            }
            return  $this->resultJsonFactory->create()->setData(['Test-Message' => $responce]);
        } else {
            $errors = "<p class='aramex_red'>No orders with 'Pending' status selected.</p>";
            return  $this->resultJsonFactory->create()->setData(['Test-Message' => $errors]);
        }
    }
    
    /**
     * Makes request to "Aramex shipment" API
     *
     * @param string $method Shipping method
     * @param array $post "Post" request
     * @return array Information from server
     */
    private function postAction($method, $post = [])
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $baseUrl = $this->helper->getWsdlPath();
        //SOAP object
        $soapClient = $this->soapClientFactory->create($baseUrl .
                    'shipping.wsdl', ['version' => SOAP_1_1,'trace' => 1, 'keep_alive' => false]);
        $aramex_errors = false;
        $errors = [];
        try {
            /* here's your form processing */
            $order = $this->order->
                loadByIncrementId($post['aramex_shipment_original_reference']);
            $major_par = $this->getParameters($order, $post);

            try {
                //create shipment call
                $auth_call = $soapClient->CreateShipments($major_par);
                if ($auth_call->HasErrors) {
                    $errors = $this->getErrorsText($auth_call);
                    return ([$errors, $method, 'error']);
                } else {
                    $data = [
                        'items' => $post['aramex_items'],
                        'comment_text' => "AWB No. " . $auth_call->Shipments->ProcessedShipment->ID . " - Order No. " .
                        $auth_call->Shipments->ProcessedShipment->Reference1,
                        'comment_customer_notify' => true,
                        'is_visible_on_front' => true
                    ];
                    
                    if ($order->canShip() && $post['aramex_return_shipment_creation_date'] == "create") {
                        $this->shipmentLoader->setOrderId($post['aramex_shipment_original_reference']);
                        $this->shipmentLoader->setShipmentId(null);
                        $this->shipmentLoader->setShipment($data);
                        $this->shipmentLoader->setTracking(null);
                        $shipment = $this->shipmentLoader->load();

                        if ($shipment) {
                            $track = $this->tracking->setNumber(
                                $auth_call->Shipments->ProcessedShipment->ID
                            )->setCarrierCode(
                                "aramex"
                            )->setTitle(
                                "Aramex Shipping"
                            );
                                        $shipment->addTrack($track);
                        }
                        if (!empty($data['comment_text'])) {
                            $shipment->addComment(
                                $data['comment_text'],
                                isset($data['comment_customer_notify']),
                                isset($data['is_visible_on_front'])
                            );

                            $shipment->setCustomerNote($data['comment_text']);
                            $shipment->setCustomerNoteNotify(isset($data['comment_customer_notify']));
                        }

                        $shipment->register();
                        $this->_saveShipment($shipment);
                        /* sending mail */
                        $this->sendEmail($order, $auth_call);
                        return ([true, $method, 'succes']);
                    } else {
                        $this->messageManager->addError('Cannot do shipment for the order.');
                    }
                }
            } catch (\Exception $e) {
                $errors = $e->getMessage();
                return [$errors, $method, 'error'];
            }
            if ($aramex_errors) {
                $strip = strstr($post['aramex_shipment_referer'], "aramexpopup", true);
                $url = $strip;
                if (empty($strip)) {
                    $url = $post['aramex_shipment_referer'];
                }
                $resultRedirect->setUrl($url . 'aramexpopup/show');
            }
        } catch (\Exception $e) {
            $errors = $e->getMessage();
            return [$errors, $method, 'error'];
        }
    }
    
    /**
     * Saves Aramex shipment
     *
     * @param object $shipment Shipment object
     * @return void
     */
    private function _saveShipment($shipment)
    {

        $shipment->getOrder()->setIsInProcess(true);
        $this->transaction->addObject(
            $shipment
        )->addObject(
            $shipment->getOrder()
        )->save();
        $this->registry->unregister('current_shipment');
    }
    
    /**
     * Gets Aramex orders
     *
     * @param $key $shipment Shipment object
     * @param string $order_id Order id
     * @param array $post Post request
     * @return array List of orders
     */
    private function getOrders($key, $order_id, $post)
    {
        $orderData = [];
        $order = $this->order->loadByIncrementId((int) $order_id);
        $history = [];
        if ($order->getSize()) {
            foreach ($order->getShipmentsCollection() as $_shipment) {
                if ($_shipment->getSize()) {
                    foreach ($_shipment->getCommentsCollection() as $_comment) {
                        $history[] = $_comment->getComment();
                    }
                }
            }
        }
        if (!empty($history)) {
            foreach ($history as $_history) {
                $awbno = strstr($_history, "- Order No", true);
            }
        }

        if ($order->getStatus() == "pending" && !isset($awbno)) {
            $shipping = $order->getShippingAddress();
            $shippingCountry = ($shipping) ? $shipping->getData('country_id') : '';
            if ($shippingCountry == $post['aramex_shipment_shipper_country']) {
                $orderData[$key]['method'] = "DOM";
            } else {
                $orderData[$key]['method'] = "EXP";
            }
            $orderData [$key]['order_id'] = $order_id;
        }
        return $orderData;
    }
    
    /**
     * Gets orders with pending status
     *
     * @param array $post_out Selected orders
     * @param array $post Post request
     * @return array List of orders with pending status
     */
    private function getOrdersWithPendingStatus($post_out, $post)
    {
        $ordersData = [];
        if (!empty($post_out["selectedOrders"])) {
            foreach ($post_out["selectedOrders"] as $key => $order_id) {
                $ordersData[] = $this->getOrders($key, $order_id, $post);
            }

            //domestic metods must be first
            $dom = [];
            $exp = [];
            foreach ($ordersData as $ordersData1) {
                foreach ($ordersData1 as $key => $order_item) {
                    if ($order_item['method'] == 'DOM') {
                        $dom[$key]['method'] = "DOM";
                        $dom[$key]['order_id'] = $order_item['order_id'];
                    } else {
                        $exp[$key]['method'] = "EXP";
                        $exp[$key]['order_id'] = $order_item['order_id'];
                    }
                }
            }

            $orders = [];
            $total = count($dom) + count($exp);
            for ($i = 0; $i < $total; $i++) {
                foreach ($dom as $key => $item) {
                    $orders[$key]['method'] = "DOM";
                    $orders[$key]['order_id'] = $item['order_id'];
                }
                foreach ($exp as $key => $item) {
                    $orders[$key]['method'] = "EXP";
                    $orders[$key]['order_id'] = $item['order_id'];
                }
            }
        }
        return $orders;
    }
    
    /**
     * Gets quantity of ordered products in order
     *
     * @param array $itemsv Orders
     * @return array List of products
     */
    private function getTotalItems($itemsv, $isShipped)
    {
        $post = [];
        foreach ($itemsv as $item) {
            if ($item->getQtyOrdered() > $item->getQtyShipped() or $isShipped) {
                $_qty = abs($item->getQtyOrdered() - $item->getQtyShipped());
                if ($_qty == 0 && $isShipped) {
                    $_qty = (int)$item->getQtyShipped();
                }
                $post[$item->getId()] = $_qty;
            }
        }
        return $post;
    }
    
    /**
     * Gets total weight of order
     *
     * @param array $itemvv Orders
     * @return string Weight
     */
    private function getTotalWeight($itemvv)
    {
        if ($itemvv->getWeight() != 0) {
            $weight = $itemvv->getWeight() * $itemvv->getQtyOrdered();
        } else {
            $weight = 0.5 * $itemvv->getQtyOrdered();
        }
        return $weight;
    }
    
    /**
     * Gets description of shipment
     *
     * @param object $order Order
     * @return string Description of order
     */
    private function getShipmentDescription($order)
    {
        $aramex_shipment_description = '';
        foreach ($order->getAllVisibleItems() as $itemname) {
            if ($itemname->getQtyOrdered() > $itemname->getQtyShipped()) {
                $aramex_shipment_description = $aramex_shipment_description . $itemname->getId() . ' - ' .
                    trim($itemname->getName());
            }
        }
                return $aramex_shipment_description;
    }
    
    /**
     * Gets errors description
     *
     * @param object $auth_call Feadbeck from Aramex server
     * @return string Errors description
     */
    private function getErrorsText($auth_call)
    {
        if (empty($auth_call->Shipments)) {
            if (count($auth_call->Notifications->Notification) > 1) {
                foreach ($auth_call->Notifications->Notification as $notify_error) {
                    $errors = 'Aramex: ' . $notify_error->Code . ' - ' . $notify_error->Message;
                }
            } else {
                $errors = 'Aramex: ' . $auth_call->Notifications->Notification->Code . ' - ' .
                    $auth_call->Notifications->Notification->Message;
            }
        } else {
            if (is_array($auth_call->Shipments->ProcessedShipment->Notifications->Notification)) {
                $notification_string = '';
                foreach ($auth_call->Shipments->ProcessedShipment->Notifications->Notification as
                    $notification_error) {
                    $notification_string .= $notification_error->Code . ' - ' .
                        $notification_error->Message . ' <br />';
                }
                $errors = $notification_string;
            } else {
                $errors = 'Aramex: ' . $auth_call->Shipments->ProcessedShipment->Notifications->
                    Notification->Code . ' - ' . $auth_call->Shipments->ProcessedShipment->
                    Notifications->Notification->Message;
            }
        }
                    return $errors;
    }
    
    /**
     * Gets errors description
     *
     * @param object $order Order
     * @param object $auth_call Feadbeck from Aramex server
     * @return string Errors description
     */
    private function sendEmail($order, $auth_call)
    {
                            /* send shipment mail */
                                $storeId = $order->getStore()->getId();
                            $copyTo = $this->helper->getEmails(self:: XML_PATH_SHIPMENT_EMAIL_COPY_TO, $storeId);
                            $copyMethod = $this->scopeConfig->getValue(
                                self::XML_PATH_SHIPMENT_EMAIL_COPY_METHOD,
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                                $storeId
                            );
                            $templateId = $this->scopeConfig->getValue(
                                self::XML_PATH_SHIPMENT_EMAIL_TEMPLATE,
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                                $storeId
                            );

        if ($order->getCustomerIsGuest()) {
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $customerName = $order->getCustomerName();
        }

                            $shipments_id = $auth_call->Shipments->ProcessedShipment->ID;
                            $templateParams = [
                                    'order' => $order,
                                    'customerName' => $customerName,
                                    'shipments_id' => $shipments_id
                                ];
                            $senderName = $this->scopeConfig->getValue(self::XML_PATH_TRANS_IDENTITY_NAME);
                            $senderEmail = $this->scopeConfig->getValue(self::XML_PATH_TRANS_IDENTITY_EMAIL);

                            if ($copyTo && $copyMethod == 'bcc') {
                                $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
                                            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                                                'store' => $storeId])
                                            ->setTemplateVars($templateParams)
                                            ->setFrom(['name' => $senderName, 'email' => $senderEmail])
                                            ->addTo($order->getCustomerEmail(), $customerName)
                                            ->addBcc($copyTo)
                                            ->getTransport();
                            }
                            if ($copyTo && $copyMethod == 'copy') {
                                $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
                                            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                                                'store' => $storeId])
                                            ->setTemplateVars($templateParams)
                                            ->setFrom(['name' => $senderName, 'email' => $senderEmail])
                                            ->addTo($order->getCustomerEmail(), $customerName)
                                            ->addBcc($copyTo)
                                            ->getTransport();
                            }
                            
                            try {
                                $transport->sendMessage();
                            } catch (\Exception $ex) {
                                $this->messageManager->addError('Unable to send email');
                            }
    }
    
    /**
     * Creates array with parameters for request to Aramex server
     *
     * @param object $order Order
     * @param array $post Post request
     * @return array Array with parameters for request to Aramex server
     */
    private function getParameters($order, $post)
    {
            $totalItems = 0;
            $items = $order->getAllItems();
            $descriptionOfGoods = '';
        foreach ($order->getAllVisibleItems() as $itemname) {
            $descriptionOfGoods .= $itemname->getId() . ' - ' . trim($itemname->getName());
        }
            $aramex_items_counter = 0;
        foreach ($post['aramex_items'] as $key => $value) {
            $aramex_items_counter++;
            if ($value != 0) {
                //itrating order items
                foreach ($items as $item) {
                    if ($item->getId() == $key) {
                        //get weight
                        if ($item->getWeight() != 0) {
                            $weight = $item->getWeight() * $item->getQtyOrdered();
                        } else {
                            $weight = 0.5 * $item->getQtyOrdered();
                        }
                        // collect items for aramex
                        $aramex_items[] = [
                            'PackageType' => 'Box',
                            'Quantity' => $post[$item->getId()],
                            'Weight' => [
                                'Value' => $weight,
                                'Unit' => 'Kg'
                            ],
                            'Comments' => $item->getName(),
                            'Reference' => ''
                        ];
                        $totalItems += $post[$item->getId()];
                    }
                }
            }
        }

            $totalWeight = $post['order_weight'];
            $params = [];
           //shipper parameters
            $params['Shipper'] = [
                'Reference1' => $post['aramex_shipment_shipper_reference'],
                'Reference2' => '',
                'AccountNumber' => ($post['aramex_shipment_info_billing_account'] == 1) ?
                $post['aramex_shipment_shipper_account'] : $post['aramex_shipment_shipper_account'],
                //Party Address
                'PartyAddress' => [
                    'Line1' => $post['aramex_shipment_shipper_street'],
                    'Line2' => '',
                    'Line3' => '',
                    'City' => $post['aramex_shipment_shipper_city'],
                    'StateOrProvinceCode' => $post['aramex_shipment_shipper_state'],
                    'PostCode' => $post['aramex_shipment_shipper_postal'],
                    'CountryCode' => $post['aramex_shipment_shipper_country'],
                ],
                //Contact Info
                'Contact' => [
                    'Department' => '',
                    'PersonName' => $post['aramex_shipment_shipper_name'],
                    'Title' => '',
                    'CompanyName' => $post['aramex_shipment_shipper_company'],
                    'PhoneNumber1' => $post['aramex_shipment_shipper_phone'],
                    'PhoneNumber1Ext' => '',
                    'PhoneNumber2' => '',
                    'PhoneNumber2Ext' => '',
                    'FaxNumber' => '',
                    'CellPhone' => $post['aramex_shipment_shipper_phone'],
                    'EmailAddress' => $post['aramex_shipment_shipper_email'],
                    'Type' => ''
                ],
            ];
            //consinee parameters
            $params['Consignee'] = [
                'Reference1' => $post['aramex_shipment_receiver_reference'],
                'Reference2' => '',
                'AccountNumber' => ($post['aramex_shipment_info_billing_account'] == 2) ?
                $post['aramex_shipment_shipper_account'] : '',
                //Party Address
                'PartyAddress' => [
                    'Line1' => $post['aramex_shipment_receiver_street'],
                    'Line2' => '',
                    'Line3' => '',
                    'City' => $post['aramex_shipment_receiver_city'],
                    'StateOrProvinceCode' => '',
                    'PostCode' => $post['aramex_shipment_receiver_postal'],
                    'CountryCode' => $post['aramex_shipment_receiver_country'],
                ],
                //Contact Info
                'Contact' => [
                    'Department' => '',
                    'PersonName' => $post['aramex_shipment_receiver_name'],
                    'Title' => '',
                    'CompanyName' => $post['aramex_shipment_receiver_company'],
                    'PhoneNumber1' => $post['aramex_shipment_receiver_phone'],
                    'PhoneNumber1Ext' => '',
                    'PhoneNumber2' => '',
                    'PhoneNumber2Ext' => '',
                    'FaxNumber' => '',
                    'CellPhone' => $post['aramex_shipment_receiver_phone'],
                    'EmailAddress' => $post['aramex_shipment_receiver_email'],
                    'Type' => ''
                ]
            ];

            // Other Main Shipment Parameters
            $params['Reference1'] = $post['aramex_shipment_info_reference'];
            $params['Reference2'] = '';
            $params['Reference3'] = '';
            $params['ForeignHAWB'] = $post['aramex_shipment_info_foreignhawb'];

            $params['TransportType'] = 0;
            $params['ShippingDateTime'] = time();
            $params['DueDate'] = time() + (7 * 24 * 60 * 60);
            $params['PickupLocation'] = 'Reception';
            $params['PickupGUID'] = '';
            $params['Comments'] = $post['aramex_shipment_info_comment'];
            $params['AccountingInstrcutions'] = '';
            $params['OperationsInstructions'] = '';
            $params['Details'] = [
                'Dimensions' => [
                    'Length' => '0',
                    'Width' => '0',
                    'Height' => '0',
                    'Unit' => 'cm'
                ],
                'ActualWeight' => [
                    'Value' => $totalWeight,
                    'Unit' => $post['weight_unit']
                ],
                'ProductGroup' => $post['aramex_shipment_info_product_group'],
                'ProductType' => $post['aramex_shipment_info_product_type'],
                'PaymentType' => $post['aramex_shipment_info_payment_type'],
                'PaymentOptions' => $post['aramex_shipment_info_payment_option'],
                'Services' => $post['aramex_shipment_info_service_type'],
                'NumberOfPieces' => $totalItems,
                'DescriptionOfGoods' => (trim($post['aramex_shipment_description']) == '') ?
                $descriptionOfGoods : $post['aramex_shipment_description'],
                'GoodsOriginCountry' => $post['aramex_shipment_shipper_country'],
                'Items' => $aramex_items,
            ];

            $params['Details']['CashOnDeliveryAmount'] = [
                'Value' => $post['aramex_shipment_info_cod_amount'],
                'CurrencyCode' => $post['aramex_shipment_currency_code']
            ];

            $params['Details']['CustomsValueAmount'] = [
                'Value' => $post['aramex_shipment_info_custom_amount'],
                'CurrencyCode' => $post['aramex_shipment_currency_code']
            ];

            $major_par['Shipments'][] = $params;
            $clientInfo = $this->helper->getClientInfo();
            $major_par['ClientInfo'] = $clientInfo;
            $major_par['LabelInfo'] = [
                'ReportID' => 9729,
                'ReportType' => 'URL'
            ];
            return $major_par;
    }
    
    /**
     * Transforms string to array
     *
     * @param string $str String for transformation
     * @return array Transformed string to array
     */
    private function unserializeForm($str)
    {
        $returndata = [];
        $strArray = explode("&", $str);
        foreach ($strArray as $item) {
            $array = explode("=", $item);
            $returndata[$array[0]] = $array[1];
        }
        return $returndata;
    }
}
