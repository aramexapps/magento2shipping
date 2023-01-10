define([
    'jquery',
    'jquery/ui'
], function ($) {

            $(document).ready(function () {

                $("#create_aramex_shipment").click(function () {
                    var classList = $(this).attr('class').split('_');
                    aramexpop(classList[1]);
                });
                $("#aramex_close").click(function () {
                    aramex_close();
                });
            });

            function aramexpop(itemscount)
            {
                if (itemscount >= 0) {
                    $("#aramex_overlay").css("display", "block");
                    $("#aramex_shipment").css("display", "block");
                    $("#aramex_shipment_creation").fadeIn(1000);
                } else {
                    alert('Cannot create a shipment, all items have been shipped');
                }
            }

            function aramex_close()
            {
                $("#aramex_shipment").css("display", "none");
                $("#aramex_overlay").css("display", "none");
            }


            /* prototype for  Zip code validate */
            function ZipValidateUpdter(country_ele, zip_ele, city_ele, optionalZipCountries, required_cls)
            {
                this.country_ele = country_ele;
                this.city_ele = city_ele;
                this.zip_ele = zip_ele;
                this.required_cls = required_cls;
                this.optionalZipCountries = optionalZipCountries;
            }
            ZipValidateUpdter.prototype.checkoptions = function () {
                var country_code = $("#" + this.country_ele).val();
                /*alert(this.country_ele+' '+country_code);*/
                if (typeof this.optionalZipCountries == 'undefined') {
                    return false;
                }

                $('.validation-advice').hide();
                if (this.optionalZipCountries.indexOf(country_code) != -1) {
                    $("#" + this.zip_ele).removeClass(this.required_cls);
                    $("#" + this.zip_ele).parent('div').find('.red').addClass('no-display');
                    $("#" + this.city_ele).addClass(this.required_cls);
                    $("#" + this.city_ele).parent('div').find('.red').removeClass('no-display');
                } else {
                    $("#" + this.zip_ele).addClass(this.required_cls);
                    $("#" + this.zip_ele).parent('div').find('.red').removeClass('no-display');
                    $("#" + this.city_ele).parent('div').find('.red').addClass('no-display');
                    $("#" + this.city_ele).removeClass(this.required_cls);
                }

            }
            function zipValidateMaker(country_ele, zip_ele, city_ele, optionalZipCountries, required_cls)
            {
                return new ZipValidateUpdter(country_ele, zip_ele, city_ele, optionalZipCountries, required_cls);
            }


});
