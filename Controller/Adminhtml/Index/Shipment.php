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
use \Magento\Backend\Model\Session;

/**
 * Controller for "Schedulepickup' functionality
 */
class Shipment extends \Magento\Backend\App\Action
{
    /**
     * Email setting
     * @var string
     */
    const XML_PATH_TRANS_IDENTITY_EMAIL = 'trans_email/ident_general/email';
    /**
     * Name setting
     * @var string
     */
    const XML_PATH_TRANS_IDENTITY_NAME = 'trans_email/ident_general/name';
    /**
     * Shipment Template setting
     * @var string
     */
    const XML_PATH_SHIPMENT_EMAIL_TEMPLATE = 'aramex/template/shipment_template';
    /**
     * Copy_to setting
     * @var string
     */
    const XML_PATH_SHIPMENT_EMAIL_COPY_TO = 'aramex/template/copy_to';

    /**
     * Copy method setting
     * @var string
     */
    const XML_PATH_SHIPMENT_EMAIL_COPY_METHOD = 'aramex/template/copy_method';

    /**
     * Object of \Magento\Framework\View\Result\PageFactory
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;
    /**
     * Object of \Magento\Framework\App\RequestInterface
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;
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
     * object of \Magento\Store\Model\StoreManagerInterface
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
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
     * object of \Magento\Framework\DB\Transaction
     * @var \Magento\Framework\DB\Transaction
     */
    private $transaction;

    /**
     * Object of \Magento\Framework\Controller\Result\JsonFactory
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var \Magento\Framework\Webapi\Soap\ClientFactory
     */
    private $soapClientFactory;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\Track
     */
    private $tracking;
    /**
     * Constructor
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory resultPageFactory
     * @param \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Aramex\Shipping\Helper\Data $helper
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\DB\Transaction $transaction
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Aramex\Shipping\Helper\Data $helper,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory,
        \Magento\Sales\Model\Order\Shipment\Track $tracking
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->shipmentLoader = $shipmentLoader;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->request = $context->getRequest();
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->order = $order;
        $this->transaction = $transaction;
        $this->soapClientFactory = $soapClientFactory;
        $this->tracking = $tracking;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $post = $this->getRequest()->getPost();
        $order_id= $this->getRequest()->getParam('order_id');
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            /* here's your form processing */
            $descriptionOfGoods = "";
        $order = $this->order->load($order_id);
        foreach ($order->getAllVisibleItems() as $itemname) {
            $descriptionOfGoods .= $itemname->getId() . ' - ' . trim($itemname->getName());
        }
        $descriptionOfGoods = mb_substr($descriptionOfGoods, 0, 65, "UTF-8");
        $major_par = $this->getParams($post, $descriptionOfGoods, $order);
        $aramex_errors = $this->makeShipment($major_par, $order, $post);
        if (isset($aramex_errors['aramex_errors'])) {
            $this->_session->setData("aramex_errors", true);

            $strip = strstr($post['aramex_shipment_referer'], "aramexpopup", true);
            $url = $strip;
            if (empty($strip)) {
                $url = $post['aramex_shipment_referer'];
            }
            $resultRedirect->setUrl($url . 'aramexpopup/show');
            return $resultRedirect;
        } else {
            $this->_session->setData("aramex_errors", false);
            $resultRedirect->setUrl($post['aramex_shipment_referer']);
            return $resultRedirect;
        }
    }
    
    /**
     * Saves shipment
     *
     * @param object $shipment Shipment
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
    }
    
    /**
     * Makes parameters collection
     *
     * @param array $post Post request
     * @param string $descriptionOfGoods Description of goods
     * @return array
     */
    private function getParams($post, $descriptionOfGoods, $order)
    {
        $storeId = $order->getStore()->getId();
        $totalItems = (trim($post['number_pieces']) == '') ? 1 : (int) $post['number_pieces'];
        //attachment
        $totalWeight = $post['order_weight'];
        $params = [];
        if ($post['aramex_shipment_shipper_account_show'] == 1) {
        $AccountNumber = $this->helper->getClientInfo();
        } else {
            $AccountNumber = $this->helper->getClientInfoCOD();
        }
            //shipper parameters
            $params['Shipper'] = [
                'Reference1' => $post['aramex_shipment_shipper_reference'],
                'Reference2' => '',
                'AccountNumber' => ($post['aramex_shipment_info_billing_account'] == 1) ?
                $AccountNumber["AccountNumber"] : $AccountNumber["AccountNumber"],
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
                    'CellPhone' => $post['aramex_shipment_shipper_mobile'],
                    'EmailAddress' => $post['aramex_shipment_shipper_email'],
                    'Type' => ''
                ],
            ];
            //consinee parameters
            $params['Consignee'] = [
                'Reference1' => $post['aramex_shipment_receiver_reference'],
                'Reference2' => '',
                'AccountNumber' => ($post['aramex_shipment_info_billing_account'] == 2 ||
                    $post['aramex_shipment_info_billing_account'] == 3) ?
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
                    'CellPhone' => $post['aramex_shipment_receiver_mobile'],
                    'EmailAddress' => $post['aramex_shipment_receiver_email'],
                    'Type' => ''
                ]
            ];
            if ($post['aramex_shipment_info_billing_account'] == 3) {
                $params['ThirdParty'] = [
                    'Reference1' => $post['aramex_shipment_shipper_reference'],
                    'Reference2' => '',
                    'AccountNumber' => $post['aramex_shipment_shipper_account'],
                    'PartyAddress' => [
                        'Line1' => $this->scopeConfig->getValue(
                            'aramex/shipperdetail/address',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        'Line2' => '',
                        'Line3' => '',
                        'City' => $this->scopeConfig->getValue(
                            'aramex/shipperdetail/city',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        'StateOrProvinceCode' => $this->scopeConfig->getValue(
                            'aramex/shipperdetail/state',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        'PostCode' => $this->scopeConfig->getValue(
                            'aramex/shipperdetail/postalcode',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        'CountryCode' => $this->scopeConfig->getValue(
                            'aramex/shipperdetail/country',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                    ],
                    'Contact' => [
                        'Department' => '',
                        'PersonName' => $this->scopeConfig->getValue(
                            'aramex/shipperdetail/name',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        'Title' => '',
                        'CompanyName' => $this->scopeConfig->getValue(
                            'aramex/shipperdetail/company',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        'PhoneNumber1' => $this->scopeConfig->getValue(
                            'aramex/shipperdetail/phone',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        'PhoneNumber1Ext' => '',
                        'PhoneNumber2' => '',
                        'PhoneNumber2Ext' => '',
                        'FaxNumber' => '',
                        'CellPhone' => $this->scopeConfig->getValue(
                            'aramex/shipperdetail/mobile',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        'EmailAddress' => $this->scopeConfig->getValue(
                            'aramex/shipperdetail/email',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        'Type' => ''
                    ],
                ];
            }

            $AdditionalPropertyDetails = array();
        if (isset($post['aramex_shipment_shipper_country']) && $post['aramex_shipment_shipper_country'] == 'IN') {
            if ($post['ShipperTaxIdVATEINNumber'] != null)
            {
                array_push($AdditionalPropertyDetails,[
                    'CategoryName' => "CustomsClearance",
                    'Name' => "ShipperTaxIdVATEINNumber",
                    'Value' => $post['ShipperTaxIdVATEINNumber']
                ]);
            }
            if ($post['ConsigneeTaxIdVATEINNumber'] != null)
            {
                array_push($AdditionalPropertyDetails,[
                    'CategoryName' => "CustomsClearance",
                    'Name' => "ConsigneeTaxIdVATEINNumber",
                    'Value' => $post['ConsigneeTaxIdVATEINNumber']
                ]);
            }
            if ($post['TaxPaid'] != null)
            {
                array_push($AdditionalPropertyDetails,[
                    'CategoryName' => "CustomsClearance",
                    'Name' => "TaxPaid",
                    'Value' => $post['TaxPaid']
                ]);
            }
            if ($post['InvoiceDate'] != null)
            {
                $orgDate = $post['InvoiceDate'];  
                $newDate = date("m/d/Y", strtotime($orgDate));  
                array_push($AdditionalPropertyDetails,[
                    'CategoryName' => "CustomsClearance",
                    'Name' => "InvoiceDate",
                    'Value' => $newDate
                ]);
            }
            if ($post['InvoiceNumber'] != null)
            {
                array_push($AdditionalPropertyDetails,[
                    'CategoryName' => "CustomsClearance",
                    'Name' => "InvoiceNumber",
                    'Value' => $post['InvoiceNumber']
                ]);
            }
            if ($post['TaxAmount'] != null)
            {
                array_push($AdditionalPropertyDetails,[
                    'CategoryName' => "CustomsClearance",
                    'Name' => "TaxAmount",
                    'Value' => $post['TaxAmount']
                ]);
            }
            if ($post['ExporterType'] != null)
            {
                array_push($AdditionalPropertyDetails,[
                    'CategoryName' => "CustomsClearance",
                    'Name' => "ExporterType",
                    'Value' => $post['ExporterType']
                ]);
            }
        }

            ////// add COD
            $services = [];
            if ($post['aramex_shipment_info_product_type'] == "CDA") {
                if ($post['aramex_shipment_info_service_type'] == null) {
                    array_push($services, "CODS");
                } elseif (!in_array("CODS", $post['aramex_shipment_info_service_type'])) {
                    $services = array_merge($services, $post['aramex_shipment_info_service_type']);
                    array_push($services, "CODS");
                } else {
                    $services = array_merge($services, $post['aramex_shipment_info_service_type']);
                }
            } else {
                if ($post['aramex_shipment_info_service_type'] == null) {
                    $post['aramex_shipment_info_service_type'] = [];
                }

                $services = array_merge($services, $post['aramex_shipment_info_service_type']);
            }
            $services = implode(',', $services);
            ///// add COD and
            // Other Main Shipment Parameters
            $itemNumbers = explode(',',$post['item_details'] ?? '');
            $itemDetails = array();
            
            foreach ($itemNumbers as $val)
            {
                if (!empty($val) || trim($val) !== '') {
                    $itemTitle = "aramex_items_Title_".$val;
                    $itemQuantity = "aramex_items_total_".$val;
                    $itemPrice = "aramex_items_base_price_".$val;
                    $itemWeight = "aramex_items_base_weight_".$val;
                    array_push($itemDetails,[
                        'Quantity' => $post[$itemQuantity],
                        'Weight' => [
                            'Value' => $post[$itemWeight],
                            'Unit' => $post['weight_unit']
                        ],
                        'GoodsDescription' => $post[$itemTitle],
                        'CustomsValue' => [
                            'Value' => $post[$itemPrice],
                            'CurrencyCode' => $post['aramex_shipment_currency_code_custom_hidden']
                        ]
                    ]);
                }
            }

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
                'Services' => $services,
                'NumberOfPieces' => $totalItems,
                'DescriptionOfGoods' => (trim($post['aramex_shipment_description']) == '') ? $descriptionOfGoods :
                $post['aramex_shipment_description'],
                'GoodsOriginCountry' => $post['aramex_shipment_shipper_country'],
                'Items' => [
                    'ShipmentItem' => $itemDetails
                ],
                'AdditionalProperties' => [
                    'AdditionalProperty' => $AdditionalPropertyDetails
                ]
            ];

            if ($post['aramex_shipment_info_cod_amount'] == "") {
                $post['aramex_shipment_info_cod_amount'] = 0;
            }
            if ($post['aramex_shipment_info_custom_amount'] == "") {
                $post['aramex_shipment_info_custom_amount'] = 0;
            }
            $params['Details']['CashOnDeliveryAmount'] = [
                'Value' => $post['aramex_shipment_info_cod_amount'],
                'CurrencyCode' => $post['aramex_shipment_currency_code']
            ];
            if ($post['aramex_shipment_info_product_group'] === 'DOM') {
                $params['Details']['CustomsValueAmount'] = [
                    'Value' => $post['aramex_shipment_info_custom_amount_hidden'],
                    'CurrencyCode' => $post['aramex_shipment_currency_code_custom_hidden']
                ];
            }
            else {
                $params['Details']['CustomsValueAmount'] = [
                    'Value' => $post['aramex_shipment_info_custom_amount'],
                    'CurrencyCode' => $post['aramex_shipment_currency_code_custom']
                ];
            }
            // Insurance
            $params['Details']['InsuranceAmount'] = [
                'Value' => $post['aramex_shipment_insurance_amount'],
                'CurrencyCode' => $post['aramex_shipment_currency_code_custom_hidden']
            ];
            $params['ShipmentDetails']['InsuranceAmount'] =  $post['aramex_shipment_insurance_amount'];
            // Insurance
            $major_par['Shipments'][] = $params;
            
            if ($post['aramex_shipment_shipper_account_show'] == 1) {
                $clientInfo = $this->helper->getClientInfo();
            } else {
                $clientInfo = $this->helper->getClientInfoCOD();
            }
            // Source
            if($post['aramex_shipment_info_payment_method'] == 'custompayment')
            {
                $clientInfo['Source'] = 47;
            } 
            // Source
            $major_par['ClientInfo'] = $clientInfo;
            $report_id = (int) $this->scopeConfig->getValue(
                'aramex/config/report_id',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        if (!$report_id) {
            $report_id = 9729;
        }
        $major_par['LabelInfo'] = [
                'ReportID' => $report_id,
                'ReportType' => 'URL'
            ];
        $this->_session->setData("form_data", $post);
        return $major_par;
    }
    
    /**
     * Email sending functionality
     *
     * @param array $post Post request
     * @param object $order Order
     * @param array $auth_call Request from Aramex server
     * @return void
     */
    private function sendEmail($post, $order, $auth_call)
    {
        /* sending mail */
        if ($post['aramex_email_customer'] == 'yes') {
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
    
            if ($copyTo == "") {
                $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
                ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                   'store' => $storeId])
                ->setTemplateVars($templateParams)
                ->setFrom(['name' => $senderName, 'email' => $senderEmail])
                ->addTo($order->getCustomerEmail(), $customerName)
                ->getTransport();
            }
    
            if ($copyTo !== "" && $copyMethod == 'bcc') {
                $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
                ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                   'store' => $storeId])
                ->setTemplateVars($templateParams)
                ->setFrom(['name' => $senderName, 'email' => $senderEmail])
                ->addTo($order->getCustomerEmail(), $customerName)
                ->addBcc($copyTo)
                ->getTransport();
            }
            if ($copyTo !== "" && $copyMethod == 'copy') {
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
                $this->messageManager->addError($ex->getMessage());
            }
        }
    }
    
    /**
     * Makes shipment functionality
     *
     * @param array $major_par Parameters
     * @param object $order Order
     * @param array $post Post request
     * @return array Feedback from Aramex server
     */
    private function makeShipment($major_par, $order, $post)
    {
        $baseUrl = $this->helper->getWsdlPath();
        //SOAP object
        $soapClient = $this->soapClientFactory->create($baseUrl .
                    'shipping.wsdl', ['version' => SOAP_1_1,'trace' => 1, 'keep_alive' => false]);
        try {
            //create shipment call
                $auth_call = $soapClient->CreateShipments($major_par);
            if ($auth_call->HasErrors) {
                $this->processError($auth_call);
                 return ['aramex_errors' => true];
            } else {
                $data = [
                        'items' => $post['aramex_items'],
                        'comment_text' => "Aramex Shipment Order AWB No. " . $auth_call->Shipments->ProcessedShipment->
                        ID . " - Order No. " . $order->getId() .
                        " <a style='color:red;'>Print Label #"  . $auth_call->Shipments->ProcessedShipment->ID ."</a>",
                        'comment_customer_notify' => true,
                        'is_visible_on_front' => true
                    ];
                if ($order->canShip() && $post['aramex_return_shipment_creation_date'] == "create") {
                    $this->shipmentLoader->setOrderId($order->getId());
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
                    if (!$shipment) {
                        $this->_forward('noroute');
                        return;
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

                    ///////////// block shipment
                    $shipment->register();
                    $this->_saveShipment($shipment);
                    $this->sendEmail($post, $order, $auth_call);
                    $this->messageManager->addSuccess(
                        'Aramex Shipment Number: ' . $auth_call->Shipments->ProcessedShipment->ID .
                                ' has been created.'
                    );
                } elseif ($post['aramex_return_shipment_creation_date'] == "return") {
                    $this->sendEmail($post, $order, $auth_call);
                    $baseUrl = $this->storeManager->getStore()->getBaseUrl();
                    $message = "Aramex Shipment Return Order AWB No. " . $auth_call->Shipments->ProcessedShipment->
                            ID . " - Order No. " . $order->getId() .
                            " <a style='color:red;'>Print Label #"  . $auth_call->Shipments->ProcessedShipment->ID .
                            "</a>";
                    $this->messageManager->addSuccess('Aramex Shipment Return Order Number: ' . $auth_call->
                            Shipments->ProcessedShipment->ID . ' has been created.');
                    $order->addStatusToHistory($order->getStatus(), $message, false);
                    $order->save();
                } else {
                    $this->messageManager->addError('Cannot do shipment for the order.');
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addError('adminhtml/session')->addError($e->getMessage());
            return ['aramex_errors' => true];
        }
    }
    /**
     * Creates error messages
     *
     * @param array $auth_call  Feedback from Aramex server
     * @return void
     */
    private function processError($auth_call)
    {
        if (empty($auth_call->Shipments)) {
            if (!is_object($auth_call->Notifications->Notification)) {
                foreach ($auth_call->Notifications->Notification as $notify_error) {
                    $this->messageManager->addError('Aramex: ' . $notify_error->Code . ' - ' .
                            $notify_error->Message);
                }
            } else {
                $this->messageManager->addError('Aramex: ' . $auth_call->Notifications->Notification->Code
                   . ' - ' . $auth_call->Notifications->Notification->Message);
            }
        } elseif (isset($auth_call->Notifications->Notification)) {
            $this->messageManager->addError('Aramex: ' . $auth_call->Notifications->Notification->Code
            . ' - ' . $auth_call->Notifications->Notification->Message);
        } else {
            if (!is_object($auth_call->Shipments->ProcessedShipment->Notifications->Notification)) {
                $notification_string = '';
                foreach ($auth_call->Shipments->ProcessedShipment->Notifications->Notification as $notification_error) {
                            $notification_string .= $notification_error->Code . ' - '
                                    . $notification_error->Message . ' <br />';
                }
                $this->messageManager->addError($notification_string);
            } else {
                $this->messageManager->addError('Aramex: ' . $auth_call->Shipments->ProcessedShipment->
                        Notifications->Notification->Code . ' - ' . $auth_call->Shipments->ProcessedShipment->
                        Notifications->Notification->Message);
            }
        }
    }
}
