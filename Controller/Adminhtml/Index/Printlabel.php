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
 * Controller for label printing
 */
class Printlabel extends \Magento\Framework\App\Action\Action
{
    /**
     * Object of \Magento\Framework\App\Config\ScopeConfigInterface
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     *  Post request
     * @var string
     */
    private $request;

    /**
     * Object of \Aramex\Shipping\Helper\Data
     * @var \Aramex\Shipping\Helper\Data
     */
    private $helper;

    /**
     * Object of \Magento\Sales\Api\OrderRepositoryInterface
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var \Magento\Framework\Webapi\Soap\ClientFactory
     */
    private $soapClientFactory;
    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context                $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Aramex\Shipping\Helper\Data                       $helper
     * @param \Magento\Sales\Api\OrderRepositoryInterface        $orderRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Aramex\Shipping\Helper\Data $helper,
        \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->request = $context->getRequest();
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;
        $this->soapClientFactory = $soapClientFactory;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $_order = $this->orderRepository->get($this->request->getParam('order_id'));
        $previuosUrl = $this->_redirect->getRefererUrl();
        $baseUrl = $this->helper->getWsdlPath();
        $soapClient = $this->soapClientFactory->create($baseUrl .
                    'shipping.wsdl', ['version' => SOAP_1_1,'trace' => 1, 'keep_alive' => false]);
        $clientInfo = $this->helper->getClientInfo();
        $awbno = $this->getStatusHistory($_order);

        if (!is_object($_order->getSize())) {
            foreach ($_order->getShipmentsCollection() as $_shipment) {
                if (!is_object($_shipment->getSize())) {
                    foreach ($_shipment->getCommentsCollection() as $_comment) {
                        $awbno[2]['comment'] = trim(strstr($_comment->getComment(), "- Order No", true), "AWB No.");
                        $awbno[2]['created'] = $_comment->getCreatedAt();
                        break;
                    }
                }
            }
            $report_id = 9729;
            $shipmentNumber = $this->request->getParam('print_label_id');
            $params = [
            'ClientInfo' => $clientInfo,
            'Transaction' => [
                'Reference1' => $_order->getIncrementId(),
                'Reference2' => '',
                'Reference3' => '',
                'Reference4' => '',
                'Reference5' => '',
            ],
            'LabelInfo' => [
                'ReportID' => $report_id,
                'ReportType' => 'URL',
            ],
            ];
            $params['ShipmentNumber'] = $shipmentNumber;
            try {
                $auth_call = $soapClient->PrintLabel($params);
                /* bof  PDF demaged Fixes debug */
                if ($auth_call->HasErrors) {
                    if (!is_object($auth_call->Notifications->Notification)) {
                        foreach ($auth_call->Notifications->Notification as $notify_error) {
                            $error = "";
                            $error.='Aramex: ' . $notify_error->Code . ' - ' . $notify_error->Message;
                        }
                        $this->messageManager->addError($error);
                        return $resultRedirect;
                    } else {
                        $this->messageManager->addError('Aramex: ' . $auth_call->Notifications->Notification->Code .
                        ' - ' . $auth_call->Notifications->Notification->Message);
                        $resultRedirect->setUrl($previuosUrl);
                        return $resultRedirect;
                    }
                }
                $filepath = $auth_call->ShipmentLabel->LabelURL;
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath($filepath);
                return $resultRedirect;
            } catch (\SoapFault $fault) {
                $this->messageManager->addError('Error : ' . $fault->faultstring);
                $resultRedirect->setUrl($previuosUrl);
                return $resultRedirect;
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $resultRedirect->setUrl($previuosUrl);
                return $resultRedirect;
            }
        } else {
            $this->messageManager->addError('Shipment is empty or not created yet.');
            $resultRedirect->setUrl($previuosUrl);
            return $resultRedirect;
        }
    }
    /**
     * Gets Order AWB Number
     *
     * @param object $_order Order
     * @return array AWB Number
     */
    private function getStatusHistory($_order)
    {
        $awbno = [];
        if ($_order->getAllStatusHistory()) {
            foreach ($_order->getAllStatusHistory() as $orderComment) {
                if ($orderComment->getComment() && preg_match(
                    '/Aramex Shipment Return Order AWB No. ([0-9]+)/',
                    $orderComment->getComment(),
                    $cmatches
                )) {
                    $awbno[1]['comment'] = $cmatches[1];
                    $awbno[1]['created'] = $orderComment->getCreatedAt();
                    break;
                } else {
                    $awbno[1]['created'] = 0;
                }
            }
        } else {
            $awbno[1]['created'] = 0;
        }
        return $awbno;
    }
}
