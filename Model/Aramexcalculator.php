<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Aramex shipping calculator
 */
class Aramexcalculator extends AbstractModel
{
    /**
     * Store id
     * @var string
     */
    const SCOPE_STORE = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    
    /**
     * Object of \Magento\Framework\App\Config\ScopeConfigInterface
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    
    /**
     * Object of \Aramex\Shipping\Helper\Data
     * @var \Aramex\Shipping\Helper\Data
     */
    private $helper;
    
    /**
     * Object of \Aramex\Shipping\Model\Carrier\Aramex\Source\Domesticmethods
     * @var \Aramex\Shipping\Model\Carrier\Aramex\Source\Domesticmethods
     */
    private $domesticmethods;
    
    /**
     * Object of \Aramex\Shipping\Model\Carrier\Aramex\Source\Internationalmethods
     * @var \Aramex\Shipping\Model\Carrier\Aramex\Source\Internationalmethods
     */
    private $internationalmethods;
    
    /**
     * Object of \Magento\Catalog\Model\Product
     * @var \Magento\Catalog\Model\Product
     */
    private $product;
    /**
     * @var \Magento\Framework\Webapi\Soap\ClientFactory
     */
    private $soapClientFactory;
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Aramex\Shipping\Helper\Data $helper
     * @param \Aramex\Shipping\Model\Carrier\Aramex\Source\Domesticmethods $domesticmethods
     * @param \Aramex\Shipping\Model\Carrier\Aramex\Source\Internationalmethods $internationalmethods
     * @param \Magento\Catalog\Model\Product $product
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Aramex\Shipping\Helper\Data $helper,
        \Aramex\Shipping\Model\Carrier\Aramex\Source\Domesticmethods $domesticmethods,
        \Aramex\Shipping\Model\Carrier\Aramex\Source\Internationalmethods $internationalmethods,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->domesticmethods = $domesticmethods;
        $this->internationalmethods = $internationalmethods;
        $this->product = $product;
        $this->soapClientFactory = $soapClientFactory;
    }
    
    /**
     * Gets Aramex shipping rate
     *
     * @param array $address Address
     * @return array Response from Aramex server
     */
    
    public function getRate($address)
    {
        if ($this->scopeConfig->
            getValue('aramex/shipperdetail/country', self::SCOPE_STORE) == $address['destination_country_code']) {
            $product_group = 'DOM';
            $allowed_methods = $this->domesticmethods->toKeyArray();
            $allowed_methods_key = 'allowed_domestic_methods';
        } else {
            $product_group = 'EXP';
            $allowed_methods = $this->internationalmethods->toKeyArray();
            $allowed_methods_key = 'allowed_international_methods';
        }

        $admin_allowed_methods = explode(',', $this->scopeConfig->
            getValue('carriers/aramex/' . $allowed_methods_key, self::SCOPE_STORE));
        $admin_allowed_methods = array_flip($admin_allowed_methods);
        $allowed_methods = array_intersect_key($allowed_methods, $admin_allowed_methods);
        $baseUrl = $this->helper->getWsdlPath(self::SCOPE_STORE);
        $clientInfo = $this->helper->getClientInfo(self::SCOPE_STORE);
        $product = $this->product->load($address['product_id']);
        $weight = $product->getWeight();
        $weight_unit = $this->scopeConfig->getValue('general/locale/weight_unit', self::SCOPE_STORE);
        $weight_unit = ($weight_unit == "lbs")? "LB": $weight_unit;
        $weight_unit = ($weight_unit == "kgs")? "KG": $weight_unit;
        $weight_unit = "KG";

        $OriginAddress = [
            'StateOrProvinceCode' => $this->scopeConfig->getValue('aramex/shipperdetail/state', self::SCOPE_STORE),
            'City' => $this->scopeConfig->getValue('aramex/shipperdetail/city', self::SCOPE_STORE),
            'PostCode' => $this->scopeConfig->getValue('aramex/shipperdetail/postalcode', self::SCOPE_STORE),
            'CountryCode' => $this->scopeConfig->getValue('aramex/shipperdetail/country', self::SCOPE_STORE),
        ];

        $DestinationAddress = [
            'StateOrProvinceCode' => "",
            'City' => $address['destination_city'],
            'PostCode' => $address['destination_post_code'],
            'CountryCode' => $address['destination_country_code'],
        ];

        $ShipmentDetails = [
            'PaymentType' => 'P',
            'ProductGroup' => $product_group,
            'ProductType' => '',
            'ActualWeight' => ['Value' => $weight, 'Unit' => $weight_unit],
            'ChargeableWeight' => ['Value' => $weight, 'Unit' => $weight_unit],
            'NumberOfPieces' => 1
        ];

        $params = [
            'ClientInfo' => $clientInfo,
            'OriginAddress' => $OriginAddress,
            'DestinationAddress' => $DestinationAddress,
            'ShipmentDetails' => $ShipmentDetails,
            'PreferredCurrencyCode' => $address['currency']
        ];

        //SOAP object
        $soapClient = $this->soapClientFactory->create($baseUrl .
                    'aramex-rates-calculator-wsdl.wsdl', ['version' => SOAP_1_1,'trace' => 1, 'keep_alive' => false]);
        $priceArr = [];
        foreach ($allowed_methods as $m_value => $m_title) {
            $params['ShipmentDetails']['ProductType'] = $m_value;
            if ($m_value == "CDA") {
                $params['ShipmentDetails']['Services'] = "CODS";
            } else {
                $params['ShipmentDetails']['Services'] = "";
            }

            try {
                $results = $soapClient->CalculateRate($params);
                if ($results->HasErrors) {
                    if (is_array($results->Notifications->Notification)) {
                        $error = "";
                        foreach ($results->Notifications->Notification as $notify_error) {
                            $error .= ('Aramex: ' . $notify_error->Code . ' - ' . $notify_error->Message) . ' ';
                        }
                        $response['error'] = $error;
                    } else {
                        $response['error'] = ('Aramex: ' . $results->Notifications->Notification->Code . ' - ' .
                            $results->Notifications->Notification->Message) . ' ';
                    }
                    $response['type'] = 'error';
                    return  $response;
                } else {
                    $response['type'] = 'success';
                    $priceArr[$m_value] = ['label' => $m_title, 'amount' => $results->TotalAmount->Value,
                        'currency' => $results->TotalAmount->CurrencyCode];
                    return  $priceArr;
                }
            } catch (\Exception $e) {
                $response['type'] = 'error';
                $response['error'] = $e->getMessage();
                return  $response;
            }
        }
    }
}
