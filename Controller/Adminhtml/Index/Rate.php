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
 * Controller for "Rate calculator' functionality
 */
class Rate extends \Magento\Framework\App\Action\Action
{

    /**
     * Object of \Magento\Framework\App\Config\ScopeConfigInterface
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Post request
     *
     * @var array
     */
    private $request;

    /**
     * Object of \Magento\Directory\Model\Config\Source\Country
     * @var \Magento\Directory\Model\Config\Source\Country
     */
    private $country;

    /**
     * Object of \Aramex\Shipping\Helper\Data
     * @var \Aramex\Shipping\Helper\Data
     */
    private $helper;
    
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
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context                $context
     * @param \Aramex\Shipping\Helper\Data                       $helper
     * @param \Magento\Directory\Model\Config\Source\Country     $country
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Aramex\Shipping\Helper\Data $helper,
        \Magento\Directory\Model\Config\Source\Country $country,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory
    ) {
        $this->request = $context->getRequest();
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->country = $country;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->soapClientFactory = $soapClientFactory;
        parent::__construct($context);
    }

    /**
     * Rate calculator
     *
     * @return Array Result of rate calculation
     */
    public function execute()
    {
        $order_id = $this->request->getParam("order_id");
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_order = $objectManager->get('Magento\Sales\Model\Order')->load($order_id);
        $storeId = $_order->getStore()->getId();
        $account = $this->scopeConfig->getValue(
            'aramex/settings/account_number',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId
        );
        $country_code = $this->scopeConfig->getValue(
            'aramex/settings/account_country_code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId
        );
        $post = $this->getRequest()->getPost();
        $response = [];
        $clientInfo = $this->helper->getClientInfo();
        try {
            $country = $this->country->toOptionArray();
            foreach ($country as $item) {
                if ($item['value'] != "") {
                    if ($item['value'] == $country_code) {
                        $countryName = $item['label'];
                    }
                }
            }
            $countryName = ($countryName) ? $countryName : "";
            ////// add COD
            if ($post['service_type'] == "CDA") {
                $aramex_services = "CODS";
            } else {
                $aramex_services = "";
            }
             ///// add COD and
            $params = [
                'ClientInfo' => $clientInfo,
                'Transaction' => [
                    'Reference1' => $post['reference']
                ],
                'OriginAddress' => [
                    'StateOrProvinceCode' => $post['origin_state'],
                    'City' => $post['origin_city'],
                    'PostCode' => $post['origin_zipcode'],
                    'CountryCode' => $post['origin_country']
                ],
                'DestinationAddress' => [
                    'StateOrProvinceCode' => $post['destination_state'],
                    'City' => $post['destination_city'],
                    'PostCode' => $post['destination_zipcode'],
                    'CountryCode' => $post['destination_country'],
                ],
                'ShipmentDetails' => [
                    'PaymentType' => $post['payment_type'],
                    'ProductGroup' => $post['product_group'],
                    'ProductType' => $post['service_type'],
                    'Services'    => $aramex_services,
                    'ActualWeight' => ['Value' => $post['text_weight'], 'Unit' => $post['weight_unit']],
                    'ChargeableWeight' => ['Value' => $post['text_weight'], 'Unit' => $post['weight_unit']],
                    'NumberOfPieces' => $post['total_count']
                ]
            ];
            $baseUrl = $this->helper->getWsdlPath();
            $soapClient = $this->soapClientFactory->create($baseUrl .
                    'aramex-rates-calculator-wsdl.wsdl', ['version' => SOAP_1_1,'trace' => 1, 'keep_alive' => false]);
            try {
                $results = $soapClient->CalculateRate($params);
                if ($results->HasErrors) {
                    if (is_array($results->Notifications->Notification)) {
                        $error = "";
                        foreach ($results->Notifications->Notification as $notify_error) {
                            $error.='Aramex: ' . $notify_error->Code . ' - ' . $notify_error->Message . "<br>";
                        }
                        $response['error'] = $error;
                    } else {
                        $response['error'] = 'Aramex: ' . $results->Notifications->Notification->Code . ' - ' .
                            $results->Notifications->Notification->Message;
                    }
                    $response['type'] = 'error';
                } else {
                    $response['type'] = 'success';
                    $amount = "<p class='amount'>" . $results->TotalAmount->Value . " " .
                        $results->TotalAmount->CurrencyCode . "</p>";
                    $text = "Local taxes - if any - are not included. Rate is based on account number $account "
                        . "in " . $countryName;
                    $response['html'] = $amount . $text;
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
