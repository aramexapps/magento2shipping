<?php

namespace Aramex\Shipping\Api;

use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;

interface AramexGuestPaymentInterface
{
    /**
     * Set payment information for a specified cart.
     *
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return \Magento\Quote\Api\Data\TotalsInterface
     */
    public function getTotals(
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    );
}
