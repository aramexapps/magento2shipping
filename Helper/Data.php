<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Helper;


use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Helper
 */
class Data extends AbstractHelper
{
    
    /**
     * Object of \Magento\Store\Model\ScopeInterface
     * @var \Magento\Store\Model\ScopeInterface
     */
    const SCOPE_STORE = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

     /**
      * Object of \Magento\Framework\Module\Dir\Reader
      * @var \Magento\Framework\Module\Dir\Reader
      */
    private $reader;
    /**
     *  Post request
     * @var string
     */
    private $request;
     /**
      * Object of \Magento\Framework\App\Config\ScopeConfigInterface
      * @var \Magento\Framework\App\Config\ScopeConfigInterface
      */
    private $scopeConfiguration;
    /**
     * Object of \Magento\Store\Model\StoreManagerInterface
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * Store id
     * @var string
     */
    private $storeId;
    /**
     * {@inheritdoc}
     * @param \Magento\Framework\Module\Dir\Reader $reader
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Module\Dir\Reader $reader,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->request = $context->getRequest();
        $this->storeManager = $storeManager;
        $this->reader = $reader;
        $this->scopeConfiguration = $scopeConfiguration;
        $this->storeId = $this->storeManager->getStore()->getId();
        $this->orderRepository = $orderRepository;
    }
    
    /**
     * Gets information about client
     *
     * @return array Information about client
     */

    public function getClientInfo()
    {   
        
        $orderId = $this->request->getParam('order_id');     
        
        if($orderId != null)
        {
            $_order = $this->orderRepository->get($orderId);
        }
        
        if ($orderId && isset($_order)) 
        {
            $storeId = (int) $_order->getStoreId();
        }
        else
        {
             $storeId = $this->storeId;
        }

        $account = $this->scopeConfiguration->getValue(
            'aramex/settings/account_number',
            self::SCOPE_STORE, 
            $storeId
        );
        $username = $this->scopeConfiguration->getValue(
            'aramex/settings/user_name',
            self::SCOPE_STORE, 
            $storeId
        );
        $password = $this->scopeConfiguration->getValue(
            'aramex/settings/password',
            self::SCOPE_STORE, 
            $storeId
        );
        $pin = $this->scopeConfiguration->getValue(
            'aramex/settings/account_pin',
            self::SCOPE_STORE,
            $storeId
        );
        $entity = $this->scopeConfiguration->getValue(
            'aramex/settings/account_entity',
            self::SCOPE_STORE,
            $storeId
        );
        $country_code = $this->scopeConfiguration->getValue(
            'aramex/settings/account_country_code',
            self::SCOPE_STORE,
            $storeId
        );

        $paymentType = $this->scopeConfiguration->getValue(
            'aramex/config/default_payment_method',
            self::SCOPE_STORE,
            $storeId
        );
        return [
            'AccountCountryCode' => $country_code,
            'AccountEntity' => $entity,
            'AccountNumber' => $account,
            'AccountPin' => $pin,
            'UserName' => $username,
            'Password' => $password,
            'Version' => 'v1.0',
            'Source' => 31,
            'PaymentType' => $paymentType
        ];
    }
    
    /**
     * Gets information about COD client
     *
     * @return array Information about COD client
     */
    public function getClientInfoCOD()
    {
        $orderId = $this->request->getParam('order_id');     
        
        if($orderId != null)
        {
            $_order = $this->orderRepository->get($orderId);
        }
        
        if ($orderId && isset($_order)) 
        {
            $storeId = (int) $_order->getStoreId();
        }
        else
        {
             $storeId = $this->storeId;
        }
        
        $account = $this->scopeConfiguration->getValue(
            'aramex/settings/cod_account_number',
            self::SCOPE_STORE,
            $storeId
        );
        $username = $this->scopeConfiguration->getValue(
            'aramex/settings/user_name',
            self::SCOPE_STORE,
            $storeId
        );
        $password = $this->scopeConfiguration->getValue(
            'aramex/settings/password',
            self::SCOPE_STORE,
            $storeId
        );
        $pin = $this->scopeConfiguration->getValue(
            'aramex/settings/cod_account_pin',
            self::SCOPE_STORE,
            $storeId
        );
        $entity = $this->scopeConfiguration->getValue(
            'aramex/settings/cod_account_entity',
            self::SCOPE_STORE,
            $storeId
        );
        $country_code = $this->scopeConfiguration->getValue(
            'aramex/settings/cod_account_country_code',
            self::SCOPE_STORE,
            $storeId
        );

        if(strtolower($country_code) !== strtolower($this->scopeConfiguration->getValue('aramex/shipperdetail/country',
            self::SCOPE_STORE, $storeId))){
            $paymentType = "3";

        }else{
            $paymentType = "P";
        }
        return [
            'AccountCountryCode' => $country_code,
            'AccountEntity' => $entity,
            'AccountNumber' => $account,
            'AccountPin' => $pin,
            'UserName' => $username,
            'Password' => $password,
            'Version' => 'v1.0',
            'Source' => 31,
            'PaymentType' => $paymentType
        ];
    }
    
    /**
     * Gets directry path with aramex wsdl files location
     *
     * @return string Directry path with aramex wsdl files location
     */
    public function getWsdlPath()
    {
        $wsdlBasePath = $this->reader->getModuleDir('etc', 'Aramex_Shipping') . '/wsdl/Aramex/';
        if ($this->scopeConfiguration->getValue(
            'aramex/config/sandbox_flag',
            self::SCOPE_STORE,
            $this->storeId
        ) == 1) {
            $path = $wsdlBasePath . 'TestMode/';
        } else {
            $path = $wsdlBasePath;
        }
        return $path;
    }
    
    /**
     * Gets default user account detailes
     *
     * @return array Default user account detailes
     */
    public function getStaticClientInfo()
    {
        return [
            'AccountCountryCode' => 'JO',
            'AccountEntity' => 'AMM',
            'AccountNumber' => '20016',
            'AccountPin' => '331421',
            'UserName' => 'testingapi@aramex.com',
            'Password' => 'R123456789$r',
            'Version' => 'v1.0',
            'Source' => null
        ];
    }
    
    /**
     * Gets admin emails
     *
     * @param string $configPath Path to configuration file
     * @param array $storeId Store id
     * @return string|bulean Admin emails
     */
    public function getEmails($configPath, $storeId)
    {
        $data = $this->scopeConfiguration->getValue(
            $configPath,
            self::SCOPE_STORE,
            $this->storeId
        );
        if (!empty($data)) {
            return explode(',', $data);
        }
        return false;
    }
    /**
     * Gets configuration detailes
     *
     * @param string $config_path Path to configuration file
     * @return array Configuration detailes
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfiguration->getValue(
            $config_path,
            self::SCOPE_STORE,
            $this->storeId
        );
    }
    /**
     * Gets configuration detailes
     *
     * @param string $config_path Path to configuration file
     * @return array Configuration detailes
     */
    public function get($config_path)
    {
        return $this->scopeConfiguration->getValue(
            $config_path,
            self::SCOPE_STORE,
            $this->storeId
        );
    }
    /**
     * Gets name of shipper
     *
     * @return string Name of shipper
     */
    public function getCode()
    {
        return 'aramex';
    }

}
