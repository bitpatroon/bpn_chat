function bpnChatNewMessages()
{
    var $ = $ || window.$;
    var settings = { url: '/?eID=tx_bpnchat', audioUrl: '/typo3conf/ext/bpn_chat/Resources/Public/Audio/alert.mp3' };

    function playSoundOnNotification()
    {
        if (!settings.audioUrl) {
            return;
        }

        // todo: check for js permission to play audio
        if (!window.bpnChatNotification) {
            window.bpnChatNotification = new Audio(settings.audioUrl);
            window.bpnChatNotification.addEventListener('loadeddata', function () {
                let duration = window.bpnChatNotification.duration;
            });
            window.bpnChatNotification.addEventListener('canplaythrough', event => {
                /* the audio is now playable; play it if permissions allow */
                window.bpnChatNotification.play();
            });
        } else {
            window.bpnChatNotification.play();

        }
    }

    function hideCurrentMessage()
    {
        $('body .bpnchat-messages-check').each(function () {
            $(this).animate({ bottom: -5, opacity: 0 });
        });
    }

    function showMessage(countText)
    {
        var $body = $('body');
        var messagesAlertBox = $body.find('.bpnchat-messages-check');

        if (!messagesAlertBox.length) {
            $body.append('<div style="display: none; opacity: 0" class="bpnchat-messages-check">Messages<span data-message="messages">' + countText + '</span></div>');
            messagesAlertBox = $body.find('.bpnchat-messages-check');
            if(settings.pluginPage){
                messagesAlertBox.addClass('clickable');
                messagesAlertBox.click(function (){
                    document.location.href = settings.pluginPage;
                });
            }
        } else {
            messagesAlertBox.find('.messages').html(countText);
        }
        messagesAlertBox.show();
        messagesAlertBox.animate({ bottom: 10, opacity: 1 }, function () {
            playSoundOnNotification();
        });

        if (settings.fadeout && settings.fadeout > 0) {
            setTimeout(function () {
                hideCurrentMessage();
                recheck();
            }, (settings.fadeout * 1000));
        }
    }

    function handleResponse(data)
    {
        // hide previous box
        hideCurrentMessage();

        var count = data.check || 0;
        if (count <= 0) {
            return;
        }

        var countText = count;
        if (count > 10) {
            countText = '10+';
        }
        showMessage(countText);
    }

    function recheck()
    {
        if (!(settings.checkInterval && settings.checkInterval > 0 && settings.checkInterval < 10000)) {
            return;
        }

        // console.log('newMessages.js:1622445063915:', 'checking in ' + settings.checkInterval + ' seconds.');
        var recheckHandle = setTimeout(function () {
            document.dispatchEvent(new CustomEvent('check-for-notifications', { detail: {}, bubbles: true }));
            clearTimeout(recheckHandle);

        }, (settings.checkInterval * 1000));
    }

    function checkForMessages()
    {
        if($('.tx-bpnchat-chat .message-input').length > 0){
            console.log('newMessages.js:1622445156835:', 'Stop checking messages. Chat plugin function found on page');
            return
        }

        var you = settings.you;
        if (!you) {
            console.log('newMessages.js:1622445156835:', 'User appears unknown. Cannot continue;');
            return;
        }
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

    function init()
    {
        var $settings = $('span[data-plugin="bpn-chat"]');
        var audio = $settings.attr('data-audio');
        var fadeout = parseInt($settings.attr('data-fadeout') || 0);
        var checkInterval = parseInt($settings.attr('data-check') || 0);
        var you = $settings.attr('data-id');
        var pluginPage = $settings.attr('data-plugin-page');

        if (audio) {
            settings.audioUrl = audio;
        }
        settings.fadeout = fadeout || 0;
        settings.checkInterval = checkInterval || 0;
        settings.you = you || 0;
        if(pluginPage){
            settings.pluginPage = pluginPage;
        }

        // $settings.remove();

        document.addEventListener('check-for-notifications', function (e) {
            const complete = e.detail.complete || function () {};
            checkForMessages();
            complete();
        }, false);

        // fire the first
        checkForMessages();

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
        if (window.bpn_chat_messages) {
            console.log('bpnChatNewMessages is already intialised by another instance. Stopping.');
            return;
        }
        window.bpn_chat_messages = 1;

        waitForJQuery(init);
    });
}

bpnChatNewMessages();
