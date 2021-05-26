function bpnChat()
{

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

    function updateMessagesScroll($chatApplicationElement)
    {
        $messagesWindow = $chatApplicationElement.find('.chat-messages');
        $messagesWindow.scrollTop($messagesWindow.prop('scrollHeight'));
    }

    function doAjaxCall(url, callback)
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

    function getMessageObj(senderId, message)
    {
        return {
            sender: { uid: senderId },
            message: message,
            crdate: Math.floor((new Date()).getTime() / 1000)
        };
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

    function handleNewMessage(uid, messageObj, $chatApplicationElement)
    {
        settings.lastMessageId = uid;
        var itemAlreadyVisible = $chatApplicationElement.find('li[data-uid="' + uid + '"]').length >= 1;
        if (itemAlreadyVisible) {
            if (settings.debug) {
                console.log('chat.js:1621930698247:', uid + ' is already rendered.');
            }
            return false;
        }
        var templateProps = determineMessagesProps(uid, messageObj);
        var template = $chatApplicationElement.find('template.' + templateProps.templateKey).html();
        var $newElement = $(`<li class="list-group-item" data-uid="${uid}">${template}</li>`);

        var $chatMessages = $chatApplicationElement.find('.chat-messages');
        $chatMessages.append($newElement);

        $newItem = $chatApplicationElement.find('li[data-uid="' + uid + '"]');
        $newItem.find('[data-field="message"]').html(templateProps.messageText);
        $newItem.find('[data-field="date"]').html(templateProps.date);
        $newItem.find('[data-field="name"]').html(templateProps.name);
        updateMessagesScroll($chatApplicationElement);
        return true;
    }

    function submitIsOnline()
    {
        var targetUrl = atob(settings.pl) + '&sh=' + settings.sh + '&operation=online';

        $.ajax({
            method: 'POST',
            data: {},
            url: targetUrl
        });
    }

    function setNotificationOnWindow()
    {
        if (settings.allowUpdate) {
            document.title = '[NEW] ' + settings.windowTitle;
        }
    }

    function notifyMessages($chatApplicationElement, amountNewMessages)
    {
        updateMessagesScroll($chatApplicationElement);
        if (amountNewMessages) {
            setNotificationOnWindow();
        }
    }

    function handleGetNewMessages($chatApplicationElement)
    {
        var url = settings.urls.get + '&uid=' + settings.lastMessageId;
        doAjaxCall(url, function (data) {
            if (!data.messages) {
                if (settings.debug) {
                    console.log('chat.js:1621880977751:', 'No new messages');
                }
                return;
            }

            var keys = Object.keys(data.messages);
            var newMessagesCount = 0;
            keys.forEach(function (uid) {
                var messageObj = data.messages[uid];
                if (handleNewMessage(uid, messageObj, $chatApplicationElement)) {
                    newMessagesCount++;
                }
            });

            notifyMessages($chatApplicationElement, newMessagesCount);
        });
    }

    function submitMessages($chatApplicationElement)
    {
        var $textArea = $chatApplicationElement.find('textarea.message-input');
        var message = $textArea.val();
        var targetUrl = atob(settings.pl) + '&sh=' + settings.sh;

        $.ajax({
            method: 'POST',
            data: { message: message },
            url: targetUrl
        }).done(function (data) {
            if (settings.othersOnlineState === 0) {
                message =  '<div class="alert alert-warning">' + settings.offlineMessage + '</div>' + message;
            }

            var messageObj = getMessageObj(settings.you, message);
            handleNewMessage(data.uid, messageObj, $chatApplicationElement);
            $textArea.val('');
        });
    }

    function initApplicationEvents($chatApplicationElement)
    {
        window.addEventListener('blur', () => {
            settings.allowUpdate = 1;
        });
        window.addEventListener('focus', () => {
            settings.allowUpdate = 0;
            document.title = settings.windowTitle;
            submitIsOnline();
        });

        $chatApplicationElement.find('textarea.message-input').keyup(function (event) {
            event.preventDefault();
            if (event.originalEvent.keyCode === 13 && event.originalEvent.ctrlKey === true) {
                submitMessages($chatApplicationElement);
            }
        });

        $chatApplicationElement.find('form').find('input[type="submit"]').click(function (event) {
            event.preventDefault();
            var $textArea = $chatApplicationElement.find('textarea.message-input');

            submitMessages($chatApplicationElement, $textArea.val());
        });
    }

    function handleIsOtherOnline($chatApplicationElement)
    {
        var url = settings.urls.get + '&operation=online';
        doAjaxCall(url, function (data) {
            settings.othersOnlineState = data.status;

            var $onlineStateElement = $chatApplicationElement.find('.online-state');
            var defaultClasses = $onlineStateElement.attr('data-default-class');

            switch (settings.othersOnlineState) {
                case 1:
                    $onlineStateElement.html($onlineStateElement.attr('data-online-text'));
                    $onlineStateElement.attr('data-is-online', 1);
                    $onlineStateElement.attr('class', defaultClasses + ' badge-success');
                    break;
                case -1:
                    $onlineStateElement.html($onlineStateElement.attr('data-away-text'));
                    $onlineStateElement.attr('data-is-online', -1);
                    $onlineStateElement.attr('class', defaultClasses + ' badge-warning');
                    break;
                default:
                    $onlineStateElement.html($onlineStateElement.attr('data-offline-text'));
                    $onlineStateElement.attr('data-is-online', 0);
                    $onlineStateElement.attr('class', defaultClasses + ' badge-danger');
                    break;
            }
        });
    }

    function initChat($chatApplicationElement)
    {
        initApplicationEvents($chatApplicationElement);

        handleIsOtherOnline($chatApplicationElement);
        handleGetNewMessages($chatApplicationElement);

        var interval = (settings.autoUpdateInterval || 0) * 1000;
        getNewMessagesTimer = window.setInterval(function () {

            var pause = window.pause || 0;
            if (pause) {
                if (settings.debug) {
                    console.log('chat.js:1621890443460:', 'paused');
                }
                return;
            }
            handleIsOtherOnline($chatApplicationElement);
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
        } else {
            $pauseButton.show();
            $pauseButton.click(function () {
                var paused = window.pause || 0;
                var $icon = $(this).find('i');
                if (paused !== 0) {
                    if (settings.debug) {
                        console.log('chat.js:1621945717098:', 'continue');
                    }
                    window.pause = 0;
                    $icon.attr('class', $icon.attr('data-on'));

                } else {
                    if (settings.debug) {
                        console.log('chat.js:1621945717098:', 'stopped');
                    }
                    window.pause = 1;
                    $icon.attr('class', $icon.attr('data-off'));
                }
            });
        }

        var $infoButton = $chatApplicationElement.find('[data-action="show_date"]');
        if (parseInt($infoButton.attr('data-show-date') || 0)) {
            $infoButton.show();
            $infoButton.click(function () {
                var showDate = parseInt($(this).attr('data-show-date') || 1);
                var $icon = $(this).find('i');
                if (showDate === 1) {
                    $(this).attr('data-show-date', 0);
                    $icon.attr('class', $icon.attr('data-off'));
                    $chatApplicationElement.find('form').addClass('state-no-date');
                } else {
                    $(this).attr('data-show-date', 1);
                    $icon.attr('class', $icon.attr('data-on'));
                    $chatApplicationElement.find('form').removeClass('state-no-date');
                }
            });
        } else {
            $chatApplicationElement.find('form').addClass('state-no-date');
            $infoButton.remove();
        }
    }

    function initChatApplications()
    {
        $('.tx-bpnchat-chat').each(function () {
            var $chat = $(this);
            $chat.find('.chat-args').each(function () {
                settings.urls.get = $(this).attr('data-url-get');
                settings.autoUpdateInterval = parseInt($(this).attr('data-auto-update-interval') || 0);
                settings.you = parseInt($(this).attr('data-you') || -1);
                settings.yourName = $(this).attr('data-your-name') || 'You';
                settings.amAdmin = parseInt($(this).attr('data-admin') || -1);
                settings.pauseBtnEnabled = parseInt($(this).attr('data-pause-btn-enabled') || 0);
                settings.otherPartyName = $(this).attr('data-other-party-name');
                settings.debug = parseInt($(this).attr('data-debug') || 0);
                settings.windowTitle = document.title;
                settings.sh = $(this).attr('data-sh');
                settings.pl = $(this).attr('data-pl');
                settings.lastMessageId = 0;
                settings.othersOnlineState = 0;
                settings.offlineMessage = $(this).attr('data-offline-message');

                $(this).remove();

                if (!settings.urls.get) {
                    return;
                }
                updateMessagesScroll($chat);
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
            }
        window.bpn_chat = 1;

        waitForJQuery(initChatApplications);
    });
}

bpnChat();
