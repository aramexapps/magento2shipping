/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals',
        'Aramex_Shipping/js/action/set-payment-and-update-totals',
        'knockout'
    ],
    function (Component, quote, priceUtils, totals, setPaymentAndUpdateTotalsAction, ko) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Aramex_Shipping/cash-on-delivery-fee',
                title: 'Cash On Delivery',
                value: ko.observable(0.0),
                shouldDisplay: ko.observable(false)
            },
            initialize: function() {
                this._super();

                quote.paymentMethod.subscribe(function(newPaymentMethod) {
                    setPaymentAndUpdateTotalsAction(newPaymentMethod)
                });

                quote.totals.subscribe((function (newTotals) {
                    this.value(this.getFormattedTotalValue(newTotals));
                    this.shouldDisplay(this.isTotalDisplayed(newTotals));
                }).bind(this));
            },
            isTotalDisplayed: function(totals) {
                return this.getTotalValue(totals) > 0;
            },
            getTotalValue: function(totals) {
                if (typeof totals.total_segments === 'undefined' || !totals.total_segments instanceof Array) {
                    return 0.0;
                }

                return totals.total_segments.reduce(function (cashOnDeliveryTotalValue, currentTotal) {
                    return currentTotal.code === 'aramex_cash_on_delivery' ? currentTotal.value : cashOnDeliveryTotalValue
                }, 0.0);
            },
            getFormattedTotalValue: function(totals) {
                return this.getFormattedPrice(this.getTotalValue(totals));
            }
        });
    }
);
