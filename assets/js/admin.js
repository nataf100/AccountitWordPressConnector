jQuery(document).ready(function ($) {
    $('.accountit-issue-invoice').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var order_id = $btn.data('order-id');
        var $msg = $btn.next('.accountit-status-msg');

        if (!confirm('Are you sure you want to issue an invoice for this order?')) {
            return;
        }

        $btn.prop('disabled', true).text('Processing...');
        $msg.text('');

        $.ajax({
            url: accountit_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'accountit_manual_issue_invoice',
                order_id: order_id,
                security: accountit_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    $msg.css('color', 'green').text('Success!');
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                } else {
                    $msg.css('color', 'red').text(response.data || 'Error');
                    $btn.prop('disabled', false).text('Issue Invoice');
                }
            },
            error: function () {
                $msg.css('color', 'red').text('Request failed');
                $btn.prop('disabled', false).text('Issue Invoice');
            }
        });
    });
});
