jQuery(window).ready(function ($) {
    var wp_ajax_url = document.location.protocol + '//' + document.location.host;
    var change_mobile_url = wp_ajax_url + '/wordpress/wp-admin/admin-ajax.php?action=tw_change_options';
    var tw_call_url = wp_ajax_url + '/wordpress/wp-admin/admin-ajax.php?action=tw_handle_twilio';

    $('form.tw-change-options').bind('submit', function (e) {
        e.preventDefault();
        $form = $(this);
        var form_data = $form.serialize();
        $.ajax({
            'method': 'post',
            'url': change_mobile_url,
            'timeout': 4000,
            'data': form_data,
            'dataType': 'json',
            'cache': false,
            success: function (data, textStatus) {
                console.log(data);
//                data = JSON.parse(data);
                if (data.status > 0) {
                    document.getElementById('tw_manage_subscription_page_id').innerHTML = '<strong style="color:green;">'+data.message+'</strong>';
                } else {
                    document.getElementById('tw_manage_subscription_page_id').innerHTML = '<strong style="color:red;">' + data.error+ '</strong>';
                }
            }, 'error': function (jqXHR, textStatus, errorThrown) {
                console.log('A jQuery Ajax error has occurred! See details below...');
                console.log(textStatus);
                console.log(errorThrown);
            }
        });
        // stop page action
        return false;
    });


    $('form.tw_handle_twilio').bind('submit', function (e) {
        console.log('twilio call');
        e.preventDefault();
        $form = $(this);
        var form_data = $form.serialize();
        $.ajax({
            'method': 'post',
            'url': tw_call_url,
            'timeout': 10000,
            'data': form_data,
            'dataType': 'json',
            'cache': false,

            success: function (data, textStatus) {
                if (data.status > 0) {
                    showMessage();
                } else {
                    document.getElementById('getCallDiv').innerHTML += '<p><strong>' + data.message + '</strong></p>';
                }
                return false;
            }
            ,
            'error':

                function (jqXHR, textStatus, errorThrown) {
                    console.log('A jQuery Ajax error has occurred! See details below...');
                    console.log(textStatus);
                    console.log(errorThrown);
                    return false;
                }
        });
        // stop page action
        return false;
    });

    function showMessage() {
        document.getElementById('getCallDiv').style.display = 'none';
        document.getElementById('msgCallDiv').style.display = 'block';
    }
});