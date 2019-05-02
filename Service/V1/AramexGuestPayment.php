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
use Aramex\Shipping\Api\AramexGuestPaymentInterface;
use Aramex\Shipping\Plugin\CheckoutAgreements\Model\BlockValidator;
use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Quote\Api\GuestCartTotalRepositoryInterface;

/**
 * Class Aramex Guest Payment
 */
class AramexGuestPayment implements AramexGuestPaymentInterface
{
    /**
     * @var MagentoGuestPaymentManagementInterface
     */
    private $guestPaymentInformationManagementInterface;
    /**
     * @var GuestCartTotalRepositoryInterface
     */
    private $guestCartTotalRepositoryInterface;
    /**
     * @var AgreementsValidator
     */
    private $blockValidator;

    public function __construct(
        GuestPaymentInformationManagementInterface $guestPaymentInformationManagementInterface,
        GuestCartTotalRepositoryInterface $guestCartTotalRepositoryInterface,
        BlockValidator $blockValidator
    ) {
        $this->guestPaymentInformationManagementInterface = $guestPaymentInformationManagementInterface;
        $this->guestCartTotalRepositoryInterface = $guestCartTotalRepositoryInterface;
        $this->blockValidator = $blockValidator;
    }

    /**
     * @inheritdoc
     */
    public function getTotals(
        $cartId,
        $email,
        PaymentInterface $paymentInterface,
        AddressInterface $addressInterface = null
    ) {
        $this->blockValidator->setIsSkipValidation(true);
        $this->guestPaymentInformationManagementInterface->savePaymentInformation(
            $cartId,
            $email,
            $paymentInterface,
            $addressInterface
        );
         return $this->guestCartTotalRepositoryInterface->get($cartId);
    }
}
