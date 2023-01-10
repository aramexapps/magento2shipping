/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        'Aramex_Shipping/js/model/shipping-rates-validator',
        'Aramex_Shipping/js/model/shipping-rates-validation-rules'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        aramexShippingRatesValidator,
        aramexShippingRatesValidationRules
    ) {
        'use strict';
        defaultShippingRatesValidator.registerValidator('aramex', aramexShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('aramex', aramexShippingRatesValidationRules);

        return Component;
    }
);
