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

use BPN\BpnChat\Configuration\BpnChatConfiguration;
use BPN\BpnChat\Domain\Model\Message;
use BPN\BpnChat\Domain\Repository\FrontendUserRepository;
use BPN\BpnChat\Domain\Repository\MessageRepository;
use BPN\BpnChat\Services\AuthorizationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class ChatController extends ActionController
{
    /**
     * @var AuthorizationService
     */
    protected $authorizationService;
    /**
     * @var BpnChatConfiguration
     */
    protected $configuration;
    /**
     * @var FrontendUserRepository
     */
    protected $frontendUserRepository;
    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;
    /**
     * @var MessageRepository
     */
    protected $messageRepository;

    /**
     * ChatController constructor.
     */
    public function __construct(
        AuthorizationService $authorizationService,
        BpnChatConfiguration $configuration,
        FrontendUserRepository $frontendUserRepository,
        MessageRepository $messageRepository,
        PersistenceManagerInterface $persistenceManager
    ) {
        $this->authorizationService = $authorizationService;
        $this->configuration = $configuration;
        $this->frontendUserRepository = $frontendUserRepository;
        $this->persistenceManager = $persistenceManager;
        $this->messageRepository = $messageRepository;
    }

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
    }

    public function indexAction()
    {
        $chatIds = $this->messageRepository->getChatPartnerIds($this->authorizationService->getUserId());
        $chatIds = $this->addAdmins($chatIds);

        $chats = $this->frontendUserRepository->findAllByUid($chatIds);

        $this->view->assign('chats', $chats);
    }

    public function chatAction(int $userId = 0)
    {
        if ($userId) {
            $others = [$userId];
        } else {
            $others = $this->configuration->getReceiverIds();
        }

        $messages = $this->messageRepository->getNewMessages($this->authorizationService->getUserId(), $others);

        // Retrieve all messages of userA with userB with user A = self
        $this->view->assign('messages', $messages);
        $this->view->assign('receiver', $userId);
    }

    public function addMessageAction(Message $message, int $receiver = 0, bool $redirect = true)
    {
        // set sender to self
        $message->setSender($this->authorizationService->getFrontendUser());

        if (0 === $message->getReceivers()->count()) {
            if ($receiver) {
                $message->addReceiver($this->frontendUserRepository->findByUid($receiver));

            // allow adding sys admins in general
            } else {
                $receivers = $this->configuration->getReceivers();
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
            $this->forward('chat', null, null, ['userId' => $receiver]);
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
            unset($chatPartnerIds[0]);
            if (isset($this->settings['receivers']) && $this->settings['receivers']) {
                $admins = GeneralUtility::intExplode(',', $this->settings['receivers']);
                foreach ($admins as $admin) {
                    $chatPartnerIds[$admin] = $admin;
                }
            }
        }

        return $chatPartnerIds;
    }
}
