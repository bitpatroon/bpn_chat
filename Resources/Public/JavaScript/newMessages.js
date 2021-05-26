function bpnChatNewMessages()
{
    var $ = $ || window.$;
    var settings = { url: '/?eID=tx_bpnchat' };

    function handleResponse(data)
    {
        var count = data.check || 0;
        var $body = $('body');

        var countText = count;
        if (count <= 0) {
            return;
        }

        if (count > 10) {
            countText = '10+';
        }

        $body.append('<div style="display: none" class="bpnchat-messages-check">Messages<span data-message="messages">' + countText + '</span></div>');
        var messagesAlertBox = $body.find('.bpnchat-messages-check');
        messagesAlertBox.show();
        messagesAlertBox.animate({ bottom: 10 });

        setTimeout(function () {
            messagesAlertBox.fadeOut();
        }, 10000);

    }

    function init()
    {
        var $settings = $('span[data-plugin="bpn-chat"]');
        var you = $settings.attr('data-id');

        $settings.remove();

        var url = settings.url + '&operation=check&you=' + you;
        $.ajax({
            type: 'GET',
            url: url,
            dataType: 'json',
            success: function (data) {
                handleResponse(data);
            }
        });
    }

    function waitForJQuery(callable)
    {
        var maxChecks = 20;
        var timerId = window.setInterval(function () {
            var $ = $ || window.$;
            if ($) {
                if (callable && (typeof (callable) === 'function')) {
                    callable();
                }

                checkfor$ = false;
                clearTimeout(timerId);
                return;
            }

            if (maxChecks <= 0) {
                clearTimeout(timerId);
                return;
            }
            --maxChecks;
        }, 1000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.bpn_chat) {
            if (settings.debug) {
                console.log('bpnChatNewMessages is already intialised by another instance. Stopping.');
            }
            return;
        }
        window.bpn_chat = 1;

        waitForJQuery(init);
    });
}

bpnChatNewMessages();