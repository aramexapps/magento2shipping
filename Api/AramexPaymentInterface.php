<?php
namespace Aramex\Shipping\Api;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

interface AramexPaymentInterface
{
    /**
     * Set payment information for a specified cart.
     *
     * @param int                   $cartId
     * @param PaymentInterface      $paymentMethod
     * @param AddressInterface|null $billingAddress
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return \Magento\Quote\Api\Data\TotalsInterface
     */
    public function getTotals(
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    );
}
