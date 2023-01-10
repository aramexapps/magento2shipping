require(['jquery', 'jquery/ui'], function ($) {
 jQuery(document).ready( function() {
    /* billing_aramex_cities and  shipping_aramex_cities */
    var billingAramexCitiesObj;
    var shippingAramexCitiesObj;
    var billing_aramex_cities_temp;
    var shipping_aramex_cities_temp;
var shipping_aramex_cities;
var billing_aramex_cities;
    var body_index = false;
    if (jQuery("body").hasClass("checkout-index-index") || jQuery("body").hasClass("customer-address-form")) {
        var body_index = true;
    }

    /* check checkout page*/
    if ((window.location.href.indexOf("/checkout/") > -1 || window.location.href.indexOf("/customer/address/") > -1) && body_index == true) {

        /* wait for checkout form*/
        var i = setInterval(function () {

            if (jQuery('#shipping').find("input[name^='city']").length || jQuery('.customer-address-form').find("input[name^='city']").length ) {
                //runValidation();
            /* stop waiting */
                clearInterval(i);
                /* wait for "next" button */
                var y = setInterval(function () {
                
                    if (jQuery('#shipping-method-buttons-container button').length) {
                        /* stop waiting and get button */
                        clearInterval(y);
                        getButtonNext();
                    }
                }, 100);

                if (active == 1) {

                    /* set HTML blocks */
                    jQuery('#shipping').find("input[name^='city']").after('<div id="aramex_loader" style="height:31px; width:31px; display:none;"></div>');
                    jQuery('.checkout-index-index').append('<div  class="aramex-modal"  style="display:none;"><div class="popup-inner">Loading Address Validation...</div></div>');
                    jQuery('#billing').find("input[name^='city']").after('<div id="aramex_loader"></div>');




                    shipping_aramex_cities_temp = shipping_aramex_cities;

                    /* get Aramex sities */
                    
if(jQuery("body").hasClass("customer-address-form")){

jQuery('.customer-address-form').append('<div  class="aramex-modal"  style="display:none;"><div class="popup-inner">Loading Address Validation...</div></div>');
jQuery('#form-validate').find("input[name^='city']").after('<div id="aramex_loader"></div>');

shippingAramexCitiesObj = AutoSearchControls('form-validate ', shipping_aramex_cities);
                	jQuery('#form-validate ').find("select[name^='country_id']").change(function () {
                        getAllCitiesJson('form-validate ', shippingAramexCitiesObj);
                    });
                    getAllCitiesJson('form-validate ', shippingAramexCitiesObj);
                    jQuery('#form-validate').find("input[name^='city']").blur(function () {
                        addressApiValidation('form-validate')
                    });
                    jQuery('#form-validate').find("select[name^='region_id']").blur(function () {
                        addressApiValidation('form-validate')
                    });
                    /* POSTCODE validate */
                    jQuery('#form-validate').find("input[name^='postcode']").blur(function () {
                        addressApiValidation('form-validate')
                    });


}else{
	shippingAramexCitiesObj = AutoSearchControls('shipping', shipping_aramex_cities);

                	jQuery('#shipping').find("select[name^='country_id']").change(function () {
                        getAllCitiesJson('shipping', shippingAramexCitiesObj);
                    });
                    getAllCitiesJson('shipping', shippingAramexCitiesObj);

                if (jQuery('#shipping').find(".action-show-popup").length == 0) {

                    jQuery('#shipping').find("input[name^='city']").blur(function () {
                        addressApiValidation('shipping')
                    });
                    jQuery('#shipping').find("select[name^='region_id']").blur(function () {
                        addressApiValidation('shipping')
                    });

                    /* POSTCODE validate */
                    jQuery('#shipping').find("input[name^='postcode']").blur(function () {
                        addressApiValidation('shipping')
                    });

                } else {
                    jQuery('#opc-new-shipping-address').find("input[name^='city']").blur(function () {
                        addressApiValidation('opc-new-shipping-address')
                    });
                    jQuery('#opc-new-shipping-address').find("select[name^='region_id']").blur(function () {
                        addressApiValidation('opc-new-shipping-address')
                    });

                    /* POSTCODE validate */
                    jQuery('#opc-new-shipping-address').find("input[name^='postcode']").blur(function () {
                        addressApiValidation('opc-new-shipping-address')
                    });
                }

}

                }
            }





        }, 100);
    }

    function billingValidation()
    {

        var i = setInterval(function () {
        
            if (jQuery('#payment').find("input[name^='city']").length && jQuery('#payment').find("select[name^='country_id']").length) {
            clearInterval(i);
                console.log("billing starts");
                jQuery('#payment').find("select[name^='country_id']").first().parent().parent().prependTo("#billing-new-address-form");

                /* delete fields if we want visit billing page again */
                jQuery('.opc-progress-bar-item._complete').click(function () {
                    jQuery('#payment').find("input[name^='city']").remove();
                    jQuery('#payment').find("input[name^='country_id']").parent().parent().remove();
                });
                if (active == 1) {
                    jQuery('#payment').find("input[name^='city']").after('<div id="aramex_loader" style="height:31px; width:31px; "></div>');
                    jQuery('.checkout-index-index').append('<div  class="aramex-modal"  style="display:none;"><div class="popup-inner">Loading Address Validation...</div></div>');

                    billing_aramex_cities_temp = billing_aramex_cities;
                    billingAramexCitiesObj = AutoSearchControls('payment', billing_aramex_cities);

                    jQuery('#payment').find("select[name^='country_id']").change(function () {
                        getAllCitiesJson('payment', billingAramexCitiesObj);
                    });
                    getAllCitiesJson('payment', billingAramexCitiesObj);
                    jQuery('#payment').find("input[name^='city']").blur(function () {
                        addressApiValidation('payment')
                    });
                    jQuery('#payment').find("select[name^='region_id']").blur(function () {
                        addressApiValidation('payment')
                    });
                    jQuery('#payment').find("input[name^='postcode']").blur(function () {
                        addressApiValidation('payment')
                    });
                }
            }
        }, 100);
    }

    function replaceStrAll(find, replace, str)
    {
        return str.replace(new RegExp(find, 'g'), replace);
    }

    function addressApiValidation(type)
    {
        var chk_city = jQuery('#' + type + '').find("input[name^='city']").val();
        var chk_region_id = jQuery('#' + type + '').find("input[name^='region_id']").val();
        var chk_postcode = jQuery('#' + type + '').find("input[name^='postcode']").val();
        var country_code = jQuery('#' + type + '').find("select[name^='country_id']").val();
        var system_base_url = getSystemBaseUrl();
        if (chk_city == '') {
            chk_city = 'default';
        }
        if (chk_city == '' || chk_region_id == '' || chk_postcode == '') {
            return false;
        } else {
            var isDisabled = true;
            if (isDisabled) {
                isDisabled = false;
                if (type == 'shipping') {
                    var container = jQuery('#shipping-buttons-container');
                    forceDisableNext(type);
                } else {
                    var container = jQuery('#billing-buttons-container');
                }
                jQuery('.aramex-modal').css("display", "block");
                jQuery.ajax({url: system_base_url + "apilocationvalidator/index/applyvalidation", data: {city: chk_city, post_code: chk_postcode, country_code: country_code}, type: 'Post',
                    success: function (result) {
                        forcetoEanbleNext(type);
                        var response = result;
                        if (!response.is_valid) {
                            if (!(response.suggestedAddresses) && response.message != '') {
                                alert(response.message);
                                jQuery('#' + type + '').find("input[name^='postcode']").val("");
                                jQuery('#' + type + '').find("input[name^='postcode']").keyup();
                            } else if (response.suggestedAddresses) {
                                jQuery('#' + type + '').find("input[name^='city']").val(response.suggestedAddresses.City);
                            }
                        }
                        isDisabled = true;
                    }
                });
            }
        }
    }

    function AutoSearchControls(type, search_city)
    {
    	var search_city;
        return jQuery('#' + type + '').find("input[name^='city']")
                .autocomplete({
                    /*source: search_city,*/
                    minLength: 3,
                    scroll: true,
                    source: function (req, responseFn) {

                        var re = jQuery.ui.autocomplete.escapeRegex(req.term);
                        var matcher = new RegExp("^" + re, "i");
                        var a = jQuery.grep(search_city, function (item, index) {

                            return matcher.test(item);
                        });
                        alert("2");
                        responseFn(a);
                    },
                    search: function (event, ui) {
                    	console.log("type", type);
                         /* open initializer */
                        jQuery('.checkout-index-index .ui-autocomplete').css('display', 'none');
                        jQuery('#' + type + ' #aramex_loader').css('display', 'block');
                        jQuery('#opc-new-' + type + '-address' + ' #aramex_loader').css('display', 'block');
                        forceDisableNext(type);
                    },
                    messages: {
                        noResults: '',
                        results: function () {}
                    },
                    response: function (event, ui) {
                        var temp_arr = [];
                        jQuery(ui.content).each(function (i, v) {
                            temp_arr.push(v.value);
                        });
                        if (temp_arr.length == 0) {
                            forcetoEanbleNext(type, true);
                        }
                        shipping_aramex_cities_temp = '';
                        var container = jQuery('#shipping-buttons-container');
                        var container = jQuery('shipping-buttons-container');
                        var isDisabled = (false ? true : false);
                        if (!isDisabled) {
                            container.removeClass('disabled');
                            container.css({opacity: 1});
                        }
                        forcetoEanbleNext(type);
                        jQuery('#' + type + ' #aramex_loader').css('display', 'none');
                        jQuery('#opc-new-' + type + '-address' + ' #aramex_loader').css('display', 'none');
                        return temp_arr;
                    }
                }).focus(function () {
            jQuery(this).autocomplete("search", "");
        });
    }

    /* after button clicked we go to billing page and start waiting */
    function getButtonNext()
    {
        jQuery('#shipping-method-buttons-container button').click(function () {
            var method_error = false;
            var radio_error = false;
            var radio_notice = false;

            if ($(".message.error").length != 0) {
                method_error = true;
            }
            if ($(".table-checkout-shipping-method input[type=radio]:checked").length == 0) {
                radio_error = true;
            }
            if ($(".message.notice").length != 0) {
                radio_notice = true;
            }
            if (method_error == false && radio_error == false && radio_notice == false) {
                billingValidation();
            }
        });
    }

    function forceDisableNext(type)
    {
        jQuery('#' + type + '-method-buttons-container button').prop("disabled", true);
        jQuery('#' + type + '-method-buttons-container').addClass('disabled');
        jQuery('#' + type + '-method-buttons-container').css('opacity', '0.2');
    }

    function forcetoEanbleNext(type)
    {
        jQuery('#' + type + '-method-buttons-container button').prop("disabled", false);
        jQuery('.aramex-modal').css("display", "none");
        jQuery('#' + type + '-method-buttons-container').removeClass('disabled');
        jQuery('#' + type + '-method-buttons-container').css('opacity', '1');
    }

    function getAllCitiesJson(type, aramexCitiesObj)
    {

        var system_base_url = getSystemBaseUrl();

        var country_code = jQuery('#' + type + '').find("select[name^='country_id']").val();

if (jQuery(jQuery('#opc-new-' + type + '-address').find("select[name^='country_id']")).hasClass("select")) {
  var country_code = jQuery('#opc-new-' + type + '-address').find("select[name^='country_id']").val();
}
        var mycities = '';
        var url_check = system_base_url + "apilocationvalidator/index/searchautocities?country_code=" + country_code;

        if (type = "payment") {
            billing_aramex_cities_temp = '';
            aramexCitiesObj.autocomplete("option", "source", url_check);
        }
        if (type = "shipping") {
            shipping_aramex_cities_temp = '';
            aramexCitiesObj.autocomplete("option", "source", url_check);
        }
    }

    function applyAramexValidator(type)
    {
        var country_code = jQuery("select[name='" + type + "[country_id]']").val();
        var system_base_url = getSystemBaseUrl();
        if (country_code) {
            jQuery.ajax({url: system_base_url + "/apilocationvalidator/index/applyapivalidator", data: {country_code: country_code}, type: 'Post', success: function (result) {
                    var response = jQuery.parseJSON(result);
                    if (typeof (response.post_code_required) != 'undefined') {
                        if (response.post_code_required == "1") {
                            jQuery("input[name='" + type + "[postcode]']").addClass('required-entry');
                        } else {
                            /* set city as requried */
                            jQuery("input[name='" + type + "[postcode]']").removeClass('required-entry');
                            jQuery("input[name='" + type + "[city]']").addClass('required-entry');
                        }
                    }
                }
            });
        }
    }
});

});