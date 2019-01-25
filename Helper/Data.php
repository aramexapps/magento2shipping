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
      * Object of \Magento\Framework\Module\Dir\Reader
      * @var \Magento\Framework\Module\Dir\Reader
      */
    private $reader;
     /**
      * Object of \Magento\Framework\App\Config\ScopeConfigInterface
      * @var \Magento\Framework\App\Config\ScopeConfigInterface
      */
    private $scopeConfiguration;

    /**
     * {@inheritdoc}
     * @param \Magento\Framework\Module\Dir\Reader $reader
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration
     */
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $reader,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration
    ) {
        $this->reader = $reader;
        $this->scopeConfiguration = $scopeConfiguration;
    }
    
    /**
     * Gets information about client
     *
     * @return array Information about client
     */
    public function getClientInfo()
    {
        $account = $this->scopeConfiguration->getValue(
            'aramex/settings/account_number',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $username = $this->scopeConfiguration->getValue(
            'aramex/settings/user_name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $password = $this->scopeConfiguration->getValue(
            'aramex/settings/password',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $pin = $this->scopeConfiguration->getValue(
            'aramex/settings/account_pin',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $entity = $this->scopeConfiguration->getValue(
            'aramex/settings/account_entity',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $country_code = $this->scopeConfiguration->getValue(
            'aramex/settings/account_country_code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return [
            'AccountCountryCode' => $country_code,
            'AccountEntity' => $entity,
            'AccountNumber' => $account,
            'AccountPin' => $pin,
            'UserName' => $username,
            'Password' => $password,
            'Version' => 'v1.0',
            'Source' => 31
        ];
    }
    
    /**
     * Gets information about COD client
     *
     * @return array Information about COD client
     */
    public function getClientInfoCOD()
    {
        $account = $this->scopeConfiguration->getValue(
            'aramex/settings/cod_account_number',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $username = $this->scopeConfiguration->getValue(
            'aramex/settings/user_name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $password = $this->scopeConfiguration->getValue(
            'aramex/settings/password',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $pin = $this->scopeConfiguration->getValue(
            'aramex/settings/cod_account_pin',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $entity = $this->scopeConfiguration->getValue(
            'aramex/settings/cod_account_entity',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $country_code = $this->scopeConfiguration->getValue(
            'aramex/settings/cod_account_country_code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return [
            'AccountCountryCode' => $country_code,
            'AccountEntity' => $entity,
            'AccountNumber' => $account,
            'AccountPin' => $pin,
            'UserName' => $username,
            'Password' => $password,
            'Version' => 'v1.0',
            'Source' => 31
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
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
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
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
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
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
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
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
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
