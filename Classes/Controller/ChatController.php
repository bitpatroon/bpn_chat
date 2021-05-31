<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 11-5-2021 14:21
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace BPN\BpnChat\Controller;

use BPN\BpnChat\Domain\Model\FrontEndUser;
use BPN\BpnChat\Domain\Model\Message;
use BPN\BpnChat\Traits\AuthorizationServiceTrait;
use BPN\BpnChat\Traits\BpnChatConfigurationTrait;
use BPN\BpnChat\Traits\FrontEndUserTrait;
use BPN\BpnChat\Traits\LanguageTrait;
use BPN\BpnChat\Traits\MessageRepositoryTrait;
use BPN\BpnChat\Traits\MessageServiceTrait;
use BPN\BpnChat\Traits\NameServiceTrait;
use BPN\BpnChat\Traits\PageRendererTrait;
use BPN\BpnChat\Traits\PersistenceManagerTrait;
use BPN\BpnChat\Traits\SecureLinkTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;

class ChatController extends ActionController
{
    use AuthorizationServiceTrait;
    use BpnChatConfigurationTrait;
    use FrontEndUserTrait;
    use MessageRepositoryTrait;
    use MessageServiceTrait;
    use PersistenceManagerTrait;
    use PageRendererTrait;
    use LanguageTrait;
    use NameServiceTrait;
    use SecureLinkTrait;

    protected function initializeAction()
    {
        if (!$this->authorizationService->isLoggedin() && 'error' !== $this->request->getControllerActionName()) {
            $this->notAllowed();

            return;
        }

        $contentObject = $this->configurationManager->getContentObject();
        if ($contentObject) {
            $data = $contentObject->data;
            if (isset($data['pages']) && $data['pages']) {
                $pages = GeneralUtility::intExplode(',', $data['pages']);
                /** @var \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $defaultQuerySettings */
                $defaultQuerySettings = $this->objectManager->get(QuerySettingsInterface::class);
                $defaultQuerySettings->setRespectStoragePage(false);
                $defaultQuerySettings->setStoragePageIds($pages);
                $this->messageRepository->setDefaultQuerySettings($defaultQuerySettings);
            }
        }

        $this->addCSSFile('/typo3conf/ext/bpn_chat/Resources/Public/CSS/chat.css');
        $this->addJsFooterFile('/typo3conf/ext/bpn_chat/Resources/Public/JavaScript/chat.js');
    }

    public function indexAction()
    {
        $me = $this->authorizationService->getUserId();

        $notBeforeTimeStamp = strtotime('-1 month');
        $chatIds = $this->messageRepository->getChatPartnerIds($me, $notBeforeTimeStamp);
        $chatIds = $this->addAdmins($chatIds);

        $chatUsers = $this->frontEndUserRepository->findAllByUid(array_keys($chatIds));
        $chats = [];
        foreach ($chatIds as $userId => $chatCrDate) {
            if ($userId == $me) {
                continue;
            }
            if (!isset($chatUsers[$userId])) {
                continue;
            }

            $chats[] = [
                'chat' => ['crdate' => $chatCrDate],
                'user' => ['uid' => $userId, 'username' => $chatUsers[$userId]->getUsername()],
            ];
        }

        $this->view->assign('chats', $chats);
        $this->view->assign('isadmin', $this->bpnChatConfiguration->userIsAnAdmin($me) ? 1 : 0);
    }

    public function chatAction(int $otherUserId = 0)
    {
        $userId = $this->authorizationService->getUserId();
        $senderIds = $this->messageService->getUserIds($userId);
        $otherUserIds = [$otherUserId];
        if ($otherUserId == 0) {
            $admins = $this->bpnChatConfiguration->getReceiverIds();
            $otherUserIds = array_merge($otherUserIds, $admins);
        }

        $otherUserIds = $this->messageService->getUserIds($otherUserIds);

        $myDefaultName = $this->getNameService()->getUserUnkown($userId);
        $myName = $this->getNameService()->getFullName($userId, false);
        if (!$myName) {
            $myName = $this->translate('chat.you');
        }

        $otherIdsList = implode(',', $otherUserIds);
        $isAdmin = in_array($userId, $this->bpnChatConfiguration->getReceiverIds());

        // Retrieve all messages of userA with userB with user A = self
        $messages = $this->messageRepository->getLastMessages($senderIds, $otherUserIds);
        foreach($messages as &$message){
            if ($message['sender']['name'] === $myDefaultName){
                $message['sender']['name'] = $myName;
            } else if ($isAdmin && $message['sender']['uid'] === 0){
                $message['sender']['name'] = $myName;
            }
        }
        unset($message);

        $this->view->assign('messages', $messages);
        $this->view->assign('receiver', $otherIdsList);
        $this->view->assign('urlget', $this->getUrl('get', $otherIdsList));
        $this->view->assign('autoUpdateInterval', $this->bpnChatConfiguration->getAutoUpdateInterval());
        $this->view->assign('isAdmin', $isAdmin ? 1 : 0);
        $this->view->assign('pause_btn_enabled', $this->bpnChatConfiguration->getPauseBtnEnabled() ? 1 : 0);
        $this->view->assign('debug', $this->bpnChatConfiguration->getDebug() ? 1 : 0);
        $this->view->assign('show_date', $this->bpnChatConfiguration->getShowDate() ? 1 : 0);

        $otherPartyName = $this->translate('user.unknown');
        if (in_array(0, $otherUserIds, true)) {
            $otherPartyName = $this->bpnChatConfiguration->getAdministratorName();
        } else {
            /** @var FrontEndUser $otherPartyUser */
            $otherPartyUser = $this->frontEndUserRepository->getFirstByUids($otherUserIds);
            if ($otherPartyUser) {
                $otherPartyName = $this->getNameService()->getFullName($otherPartyUser);
            }
        }

        $this->view->assign('current_users_name_tech', $myName);
        $this->view->assign('otherPartyName', $otherPartyName);
        $this->view->assign('postLinkHash', $this->generateLinkHash(['you' => $userId]));
        $this->view->assign('postLink', $this->getUrl('post', $otherIdsList));

        $this->view->assign(
            'offlineMessage', $isAdmin
            ? $this->bpnChatConfiguration->getOfflineMessageForUser()
            : $this->bpnChatConfiguration->getOfflineMessage());
    }

    public function addMessageAction(Message $message, int $receiver = 0, bool $redirect = true)
    {
        // set sender to self
        $message->setSender($this->authorizationService->getFrontendUser());

        if (0 === $message->getReceivers()->count()) {
            if ($receiver) {
                $receiverUser = $this->frontEndUserRepository->findByUid($receiver);
                $message->addReceiver($receiverUser);
                // allow adding sys admins in general
            } else {
                $receivers = $this->bpnChatConfiguration->getReceivers();
                if (!$receivers) {
                    $this->error('no_receivers_set', 1620811702, true);

                    return;
                }

                // set the default receiver
                $message->setReceivers($receivers);
            }
        }

        $message->setDelivered(0);

        $this->persistenceManager->add($message);
        $this->persistenceManager->persistAll();

        if ($redirect) {
            $this->forward('chat', null, null, ['otherUserId' => $receiver]);
        }
    }

    /**
     * Action showing error page when not allowed to view.
     */
    public function errorAction(int $errorCode = 0, string $message = '', string $key = '')
    {
        $this->view->assign('errorCode', $errorCode);
        $this->view->assign('message', $message);
        $this->view->assign('key', $key);
    }

    /**
     * @return $this
     */
    private function error(string $message, int $errorCode, bool $isKey = false)
    {
        $this->forward('error', null, null, ['errorCode' => $errorCode, ($isKey ? 'key' : 'message') => $message]);

        return $this;
    }

    /**
     * @return $this
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    private function notAllowed()
    {
        $this->forward('error', null, null, ['key' => 'not_allowed']);

        return $this;
    }

    private function addAdmins(array $chatPartnerIds)
    {
        if (!$chatPartnerIds) {
            return [];
        }
        if (isset($chatPartnerIds[0])) {
            $timeStampAdmins = $chatPartnerIds[0];
            unset($chatPartnerIds[0]);
            if (isset($this->settings['receivers']) && $this->settings['receivers']) {
                $admins = GeneralUtility::intExplode(',', $this->settings['receivers']);
                foreach ($admins as $admin) {
                    $chatPartnerIds[$admin] = $timeStampAdmins;
                }
            }
        }
        arsort($chatPartnerIds);

        return $chatPartnerIds;
    }

    private function getUrl(string $urlId, string $otherUserIds)
    {
        switch ($urlId) {
            case 'get':
                return sprintf(
                    "/index.php?eID=tx_bpnchat&you=%s&other=%s",
                    $this->authorizationService->getUserId(),
                    $otherUserIds
                );

            case 'post':
                return base64_encode(
                    sprintf(
                        "/index.php?eID=tx_bpnchat&you=%s&other=%s",
                        $this->authorizationService->getUserId(),
                        $otherUserIds
                    )
                );
            default:
                return '';
        }
    }
}
