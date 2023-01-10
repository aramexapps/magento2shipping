define([
    'jquery',
    'jquery/ui'
], function ($) {
    $.widget('aramex.aramexmass', {
        _create: function () {



$(document).ready(function () {
    $("#aramex_shipment_creation_submit_id").click(function () {
        aramexsend();
    });
    
    $(".aramexclose").click(function () {
        aramexclose();
    });

    $('.baulk_aramex_shipment').removeAttr('onclick');

    $('.baulk_aramex_shipment').click(function () {
        aramexmass();
    });

});

function aramexmass()
{
    var selected = [];
    $(".aramex_result").empty().css('display','none');
    $('.data-row input:checked').each(function () {
        selected.push($(this).parent().parent().next().children().text().trim());
    });
    if (selected.length === 0) {
        alert("Select orders, please");
    } else {
        $(".order_in_background").fadeIn(500);
        $(".aramex_bulk").fadeIn(500);
    }
}
function aramexclose()
{
    $(".order_in_background").fadeOut(500);
    $(".aramex_bulk").fadeOut(500);
}
function aramexredirect()
{
    window.location.reload(true);
}

function aramexsend()
{
    var selected = [];
    var str = $("#massform").serialize();
    $('.data-row input:checked').each(function () {
        selected.push($(this).parent().parent().next().children().text().trim());
    });
    aramexclose();
    $('.popup-loading').css('display','block');
    var url = $('.hidden_url').text();
    $.ajax({
        url: url,
        type: "POST",
        data: {selectedOrders: selected, str:str, bulk:"bulk", form_key:FORM_KEY},
        success: function ajaxViewsSection(data)
        {
            $('.popup-loading').css('display','none');
            $(".aramex_result").empty().css('display','none');
            $(".order_in_background").fadeIn(500);
            $(".aramex_bulk").fadeIn(500);
            $(".aramex_result").css("display","block");
            $(".aramex_result").append(data['Test-Message']);
            $(".aramexclose").click(function () {
                aramexredirect();
            });
        }
    });
}

$(document).ready(function () {
    // $("#aramex_shipment_info_product_type").chained("#aramex_shipment_info_product_group");
});


        }
    });

    return $.aramex.aramexmass;
});
