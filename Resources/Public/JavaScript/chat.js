(function () {

    var $ = $ || window.$;
    var settings = { urls: { get: '', getNew: '' }, autoUpdateInterval: 0, you: -1, amAdmin: -1 };
    var getNewMessagesTimer = null;

    function formatDate(timeStamp)
    {
        var date = new Date(timeStamp * 1000);

        var d = date.getDate();
        var mm = String(date.getMonth() + 1).padStart(2, '0'); //January is 0!
        var yyyy = date.getFullYear();

        var HH = date.getHours();
        var minutes = date.getMinutes();

        return d + '-' + mm + '-' + yyyy + ' ' + HH + ':' + minutes;
    }

    function getNewMessages(url, callback)
    {
        $.ajax({
            type: 'GET',
            url: url,
            dataType: 'json',
            success: function (data) {
                callback(data);
            }
        });
    }

    function determineMessagesProps(uid, messageObj)
    {
        var iSentThisMessage = (messageObj['sender']['uid'] === settings.you) || ((settings.you === 0) && settings.amAdmin);

        return {
            messageText: messageObj['message'],
            name: (iSentThisMessage ? settings.yourName : settings.otherPartyName),
            date: formatDate(parseInt(messageObj['crdate'])),
            templateKey: (iSentThisMessage ? 'iamsender' : 'iamreceiver')
        };
    }

    function handleNewMessages(uid, messageObj, $chatApplicationElement)
    {
        var itemAlreadyVisible = $chatApplicationElement.find('li[data-uid="' + uid + '"]').length >= 1;
        if (itemAlreadyVisible) {
            console.log('chat.js:1621930698247:', uid + ' is already rendered.');
            return;
        }
        var templateProps = determineMessagesProps(uid, messageObj);
        var template = $chatApplicationElement.find('template.' + templateProps.templateKey).html();
        var $newElement = $(`<li class="list-group-item" data-uid="${uid}">${template}</li>`);

        var $entryForm = $chatApplicationElement.find('li[data-type="entry-form"]');
        $entryForm.before($newElement);
        $newElement = $entryForm.prev();
        $newElement.find('[data-field="message"]').html(templateProps.messageText);
        $newElement.find('[data-field="date"]').html(templateProps.date);
        $newElement.find('[data-field="name"]').html(templateProps.name);
    }

    function handleGetNewMessages($chatApplicationElement)
    {
        getNewMessages(settings.urls.getNew, function (data) {
            if (!data.messages) {
                // no updates
                console.log('chat.js:1621880977751:', 'No new messages');
                return;
            }

            var keys = Object.keys(data.messages);

            keys.forEach(function (uid) {
                var messageObj = data.messages[uid];
                handleNewMessages(uid, messageObj, $chatApplicationElement);
            });
        });
    }

    function initChat($chatApplicationElement)
    {
        handleGetNewMessages($chatApplicationElement);

        var interval = (settings.autoUpdateInterval || 0) * 1000;
        getNewMessagesTimer = window.setInterval(function () {

            var pause = window.pause || 0;
            if (pause) {
                console.log('chat.js:1621890443460:', 'paused');
                return;
            }
            handleGetNewMessages($chatApplicationElement);

            if (settings.autoUpdateInterval <= 0) {
                clearTimeout(getNewMessagesTimer);
            }
        }, interval);
    }

    function initButtons($chatApplicationElement)
    {
        var $pauseButton = $chatApplicationElement.find('[data-action="pause"]');
        if (!settings.pauseBtnEnabled) {
            $pauseButton.remove();

            return;
        }

        $pauseButton.click(function () {
            var paused = $(this).attr('data-paused');
            if (paused === '1') {
                $(this).attr('data-paused', 0);
                $(this).html('Running');
                $(this).addClass('btn-primary');
                window.pause = 0;

            } else {
                $(this).attr('data-paused', 1);
                window.pause = 1;
                $(this).html('Stopped');
                $(this).removeClass('btn-primary');
            }
        });
    }

    function initChatApplications()
    {
        $('.tx-bpnchat-chat').each(function () {
            var $chat = $(this);
            $chat.find('.chat-args').each(function () {
                settings.urls.get = $(this).attr('data-url-get');
                settings.urls.getNew = $(this).attr('data-url-getnew');
                settings.autoUpdateInterval = parseInt($(this).attr('data-auto-update-interval') || 0);
                settings.you = parseInt($(this).attr('data-you') || -1);
                settings.yourName = $(this).attr('data-your-name') || 'You';
                settings.amAdmin = parseInt($(this).attr('data-admin') || -1);
                settings.pauseBtnEnabled = parseInt($(this).attr('data-pause-btn-enabled') || 0);
                settings.otherPartyName = $(this).attr('data-other-party-name');

                $(this).remove();

                if (!settings.urls.getNew) {
                    return;
                }

                initChat($chat);
                initButtons($chat);
            });
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
            console.log('bpn_chat is already intialised by another instance. Stopping.');
            return;
        }
        window.bpn_chat = 1;

        waitForJQuery(initChatApplications);
    });
})();