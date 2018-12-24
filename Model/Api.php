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
     * Class to get list of cities
     */
class Api extends AbstractModel
{
    /**
     * Object of \Magento\Framework\Controller\Result\JsonFactory
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    /**
     * Object of \Aramex\Shipping\Helper\Data
     * @var \Aramex\Shipping\Helper\Data
     */
    private $helper;
    /**
     * {@inheritdoc}
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Aramex\Shipping\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Aramex\Shipping\Helper\Data $helper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
    }
    
    /**
     * Gets list of cities
     *
     * @param string $CountryCode Code Code of country
     * @param string $NameStartsWith Fierst letters of city
     * @return array
     */
    public function fetchCities($CountryCode, $NameStartsWith = null)
    {
        $clientInfo = $this->helper->getClientInfo();

        $params = [
            'ClientInfo' => $clientInfo,
            'Transaction' => [
                'Reference1' => '001',
                'Reference2' => '002',
                'Reference3' => '003',
                'Reference4' => '004',
                'Reference5' => '005'
            ],
            'CountryCode' => $CountryCode,
            'State' => null,
            'NameStartsWith' => $NameStartsWith,
        ];

        $baseUrl = $this->helper->getWsdlPath();
        //SOAP object
        $soapClient = new \Zend\Soap\Client($baseUrl . 'Location-API-WSDL.wsdl');
        $soapClient->setSoapVersion(SOAP_1_1);

        try {
            $results = $soapClient->FetchCities($params);
            if (is_object($results)) {
                if (!$results->HasErrors) {
                    $cities = isset($results->Cities->string) ? $results->Cities->string : false;
                    return $cities;
                }
            }
        } catch (\SoapFault $fault) {
            return  $this->resultJsonFactory->create()->setData($this->__('Error : '). $fault->faultstring);
        }
    }
    
    /**
     * Validates address
     *
     * @param array $address Address
     * @return array Response
     */
    public function validateAddress($address)
    {
        $clientInfo = $this->helper->getClientInfo();
        $params = [
            'ClientInfo' => $clientInfo,
            'Transaction' => [
                'Reference1' => '001',
                'Reference2' => '002',
                'Reference3' => '003',
                'Reference4' => '004',
                'Reference5' => '005'
            ],
            'Address' => [
                'Line1' => '001',
                'Line2' => '',
                'Line3' => '',
                'City' => $address['city'],
                'StateOrProvinceCode' => '',
                'PostCode' => $address['post_code'],
                'CountryCode' => $address['country_code']
            ]
        ];

        $baseUrl = $this->helper->getWsdlPath();
        //SOAP object
        $soapClient = new \Zend\Soap\Client($baseUrl . 'Location-API-WSDL.wsdl', ['cache_wsdl' => WSDL_CACHE_NONE]);
        $soapClient->setSoapVersion(SOAP_1_1);
        $reponse = [];
        try {
            $results = $soapClient->ValidateAddress($params);
            if (is_object($results)) {
                if ($results->HasErrors) {
                    $suggestedAddresses = (isset($results->SuggestedAddresses->Address)) ? $results->
                        SuggestedAddresses->Address : "";
                    $message = $results->Notifications->Notification->Message;
                    $reponse = ['is_valid' => false, 'suggestedAddresses' => $suggestedAddresses,
                        'message' => $message];
                } else {
                    $reponse = ['is_valid' => true];
                }
            }
        } catch (\SoapFault $fault) {
            return  $this->resultJsonFactory->create()->setData($this->__('Error : '). $fault->faultstring);
        }
        return $reponse;
    }
}
