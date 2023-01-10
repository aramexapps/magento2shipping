<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Catalog\Block\Product\View;

use Magento\Catalog\Block\Product\AbstractProduct;
use \Magento\Customer\Model\Session;

/**
 * Controller for "Product" page
 */
class Index extends AbstractProduct
{
     /**
      * Object of \Magento\Customer\Model\Session
      *
      * @var \Magento\Customer\Model\Session
      */
    private $customerSession;
     /**
      * Object of \Magento\Customer\Api\CustomerRepositoryInterface
      *
      * @var \Magento\Customer\Api\CustomerRepositoryInterface
      */
    private $customerRepositoryInterface;
     /**
      * Object of \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
      *
      * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
      */
    private $countryCollectionFactory;

    /**
     * Constructor
     *
     * @param \Magento\Catalog\Block\Product\Context                           $context
     * @param \Magento\Customer\Model\Session                                  $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface                $customerRepositoryInterface
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Framework\ObjectManagerInterface                        $objectmanager
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $registry
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->countryCollectionFactory = $countryCollectionFactory;
        $this->context = $context;
        $this->_objectManager = $objectmanager;
        $this->customerSession = $customerSession;
        $this->registry = $registry;
        parent::__construct($context);
    }

    /**
     * Checks if user was logged
     *
     * @return boolean True if logged
     */
    public function ifLogged()
    {
        return $this->customerSession->isLoggedIn();
    }
    
    /**
     * Gets product id
     *
     * @return string Product id
     */
    public function getId()
    {
        return $this->registry->registry('current_product')->getId();
    }
    /**
     * Gets store Id
     *
     * @return string Store id
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }
    
    /**
     * Gets current currency code
     *
     * @return string Currency code
     */
    public function getCurrentCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }
    
    /**
     * Gets Base url
     *
     * @return string Base url
     */
    public function getBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
    }

    /**
     * Gets countries array
     *
     * @return array Countries
     */
    public function getCountries()
    {
        $collection = $this->countryCollectionFactory->create()->loadByStore();
        return $collection->toOptionArray();
    }
    
    /**
     * Gets Destination address
     *
     * @return array|null Destination address
     */
    public function getDestinationAddress()
    {
        $customerAddressId = $this->customerSession->getCustomer()->getDefaultShipping();
        if ($customerAddressId) {
            $customerId = $this->customerSession->getCustomer()->getId();
            $customer = $this->customerRepositoryInterface->getById($customerId);
            $customerAddress = $customer->getAddresses();
            return $customerAddress[0];
        } else {
            return null;
        }
    }

    /**
     * If aramexcalculator is active
     *
     * @return string If aramexcalculator is active
     */
    public function isActive()
    {
        return $this->scopeConfig->getValue(
            'aramex/aramexcalculator/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
