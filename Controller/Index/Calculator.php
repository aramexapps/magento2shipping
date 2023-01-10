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
     * Controller for "Rate calculator" functionality
     */
class Calculator extends Action
{
    /**
     * Object of \Magento\Framework\App\RequestInterface
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;
    
     /**
      * Object of \Magento\Framework\Data\Form\FormKey
      * @var \Magento\Framework\Data\Form\FormKey
      */
    private $formKey;

     /**
      * Object of \Magento\Framework\Controller\Result\JsonFactory
      * @var \Magento\Framework\Controller\Result\JsonFactory
      */
    private $resultJsonFactory;
    
     /**
      * Object of \Aramex\Shipping\Model\Aramexcalculator
      * @var \Aramex\Shipping\Model\Aramexcalculator
      */
    private $aramexcalculator;
    /**
     * {@inheritdoc}
     * @param  Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Aramex\Shipping\Model\Aramexcalculator $aramexcalculator
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Aramex\Shipping\Model\Aramexcalculator $aramexcalculator,
        \Magento\Framework\Data\Form\FormKey $formKey
    ) {
        parent::__construct($context);
        $this->request = $context->getRequest();
        $this->resultJsonFactory = $resultJsonFactory;
        $this->aramexcalculator = $aramexcalculator;
        $this->formKey = $formKey;
    }
    /**
     * {@inheritdoc}
     */
    public function execute()
    {

        if ($this->request->getParam('form_key') === null || $this->request->getParam('form_key') !=
            $this->formKey->getFormKey()) {
            return  $this->resultJsonFactory->create()->setData($this->__('Invalid form data.'));
        }
        $address = [];
        $address['destination_city'] = $this->request->getParam('city');
        $address['destination_post_code'] = $this->request->getParam('post_code');
        $address['destination_country_code'] = $this->request->getParam('country_code');
        $address['currency'] = $this->request->getParam('currency');
        $address['storeId'] = $this->request->getParam('store_id');
        $address['product_id'] = $this->request->getParam('product_id');
        $response = $this->aramexcalculator->getRate($address);
        return  $this->resultJsonFactory->create()->setData($response);
    }
}
