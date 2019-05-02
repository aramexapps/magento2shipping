<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Service\V1;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Aramex\Shipping\Api\AramexPaymentInterface;
use Aramex\Shipping\Plugin\CheckoutAgreements\Model\BlockValidator;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;

/**
 * Class Aramex Payment
 */
class AramexPayment implements AramexPaymentInterface
{
    /**
     * @var PaymentInformationManagementInterface
     */
    private $paymentInformationManagementInterface;
    /**
     * @var CartTotalRepositoryInterface
     */
    private $cartTotalRepositoryInterface;
    /**
     * @var AgreementsValidator
     */
    private $blockValidator;

    public function __construct(
        PaymentInformationManagementInterface $paymentInformationManagementInterface,
        CartTotalRepositoryInterface $cartTotalRepositoryInterface,
        BlockValidator $blockValidator
    ) {
        $this->paymentInformationManagementInterface = $paymentInformationManagementInterface;
        $this->cartTotalRepositoryInterface = $cartTotalRepositoryInterface;
        $this->blockValidator = $blockValidator;
    }

    /**
     * @inheritdoc
     */
    public function getTotals(
        $cartId,
        PaymentInterface $paymentInterface,
        AddressInterface $addressInterface = null
    ) {

        $this->blockValidator->setIsSkipValidation(true);

        $this->paymentInformationManagementInterface->savePaymentInformation(
            $cartId,
            $paymentInterface,
            $addressInterface
        );

        return $this->cartTotalRepositoryInterface->get($cartId);
    }
}
