<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

    /**
     * Controller for "Search cities" functionality
     */
class Searchautocities extends Action
{
    /**
     * Object of \Magento\Framework\App\RequestInterface
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

     /**
      * Object of \Magento\Framework\Controller\Result\JsonFactory
      * @var \Magento\Framework\Controller\Result\JsonFactory
      */
    private $resultJsonFactory;
     /**
      * Object of \Aramex\Shipping\Model\Api
      * @var \Aramex\Shipping\Model\Api
      */
    private $api;
    
    /**
     * {@inheritdoc}
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Aramex\Shipping\Model\Api $api
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Aramex\Shipping\Model\Api $api
    ) {
        parent::__construct($context);
        $this->request = $context->getRequest();
        $this->resultJsonFactory = $resultJsonFactory;
        $this->api = $api;
    }
    
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $post = $this->request->getPost();
        if ($post) {
            $countryCode = $this->request->getParam('country_code');
            $term= $this->request->getParam('term');
        }

        $cities = $this->api->fetchCities($countryCode, $term);
        if (!empty($cities) && $cities != false) {
            $cities = array_unique($cities);
            $sortCities = [];
            foreach ($cities as $v) {
                $sortCities[] = ucwords(strtolower($v));
            }
            asort($sortCities, SORT_STRING);
            $to_return = [];
            foreach ($sortCities as $val) {
                $to_return[] =  $val ;
            }
            return  $this->resultJsonFactory->create()->setData($to_return);
        } else {
            return  $this->resultJsonFactory->create()->setData([]);
        }
    }
}
