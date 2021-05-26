<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 22-5-2021 23:25
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

namespace BPN\BpnChat;

use BPN\BpnChat\Domain\Model\Message;
use BPN\BpnChat\Domain\Repository\MessageRepository;
use BPN\BpnChat\Domain\Repository\OnlineRepository;
use BPN\BpnChat\Traits\AuthorizationServiceTrait;
use BPN\BpnChat\Traits\FrontEndUserTrait;
use BPN\BpnChat\Traits\LanguageTrait;
use BPN\BpnChat\Traits\MessageRepositoryTrait;
use BPN\BpnChat\Traits\OnlineRepositoryTrait;
use BPN\BpnChat\Traits\PersistenceManagerTrait;
use BPN\BpnChat\Traits\SecureLinkTrait;
use Cest\Commons\Generic\Exception;
use RuntimeException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class Start
{
    use AuthorizationServiceTrait;
    use FrontEndUserTrait;
    use LanguageTrait;
    use OnlineRepositoryTrait;
    use PersistenceManagerTrait;
    use SecureLinkTrait;
    use MessageRepositoryTrait;

    const FAILURE = 'failure';

    public function process()
    {
        $operation = GeneralUtility::_GP('operation') ?? '';

        try {
            $this->validateArguments();
            $method = strtoupper($_SERVER['REQUEST_METHOD']);

            switch ($method) {
                case 'GET':
                    if ($operation === 'online') {
                        $you = (int) $this->getRequiredArgument('you');
                        $others = $this->getRequiredArgument('other');
                        $result['status'] = $this->getOtherIsOnline($you, $others);
                        break;
                    } elseif ($operation === 'check') {
                        $you = (int) $this->getRequiredArgument('you');
                        $result['check'] = $this->checkForNewMessages($you);
                        break;
                    }
                    $you = $this->getRequiredArgument('you');
                    $others = $this->getRequiredArgument('other');
                    $lastUid = (int) $this->getRequiredArgument('uid');

                    return $this->getNewChatMessages($you, $others, $lastUid);

                case 'POST':
                    if ($operation === 'online') {
                        $you = (int) $this->getRequiredArgument('you');
                        $others = $this->getRequiredArgument('other');

                        $result['message'] = $this->setOnline($you, $others);
                        break;
                    }

                    $you = (int) $this->getRequiredArgument('you');
                    $others = $this->getRequiredArgument('other');
                    $message = $this->getRequiredArgument('message');

                    return $this->addMessage($you, $others, $message);

                default:
                    throw new RuntimeException($this->translate('no.such.operation'), 1621859456);
            }
        } catch (\Exception $exception) {
            $result = [
                'error-code' => $exception->getCode(),
                'error'      => 'error',
                'message'    => $exception->getMessage(),
            ];
            if (Environment::getContext()->isProduction()) {
                $result['message'] = self::FAILURE;
            }
        }

        return new JsonResponse($result);
    }

    private function getNewChatMessages(string $userId, string $others, int $newerThanUid = 0): JsonResponse
    {
        $senderIds = GeneralUtility::intExplode(',', $userId);
        $otherUserIds = GeneralUtility::intExplode(',', $others);

        $result = [];
        $result['messages'] = $this->getMessageRepository()->getNewMessages($senderIds, $otherUserIds, $newerThanUid);
        $result['count'] = count($result['messages']);

        return new JsonResponse($result);
    }

    private function addMessage(int $you, string $others, string $message)
    {
        $receiverIds = GeneralUtility::intExplode(',', $others);
        $receivers = $this->getFrontEndUserRepository()->getUsersByIds($receiverIds);
        $sender = $this->getFrontEndUserRepository()->findByUid($you);

        $messageObj = new Message();
        $messageObj
            ->setSender($sender)
            ->setMessage($message)
            ->setReceivers($receivers);

        $result = ['result' => 1];
        try {
            $persistanceManager = $this->getPersistenceManager();
            $persistanceManager->add($messageObj);
            $persistanceManager->persistAll();

            $result['uid'] = $messageObj->getUid();
        } catch (Exception $exception) {
            $result['code'] = $exception->getCode();
            $result['error'] = $exception->getMessage();
            $result['result'] = 0;
        }

        return new JsonResponse($result);
    }

    private function getRequiredArgument(string $argumentId)
    {
        if (!empty($argumentId)) {
            $value = $this->getArgument($argumentId) ?? '';
            if ('' !== $value) {
                return $value;
            }
        }
        throw new RuntimeException('Required value "'.$argumentId.'" is not passed or was empty', 1621860676);
    }

    private function getArgument(string $argumentId)
    {
        return GeneralUtility::_GP($argumentId) ?? '';
    }

    private function getRequiredIntArgument(string $argumentId)
    {
        $value = (int) $this->getRequiredArgument($argumentId);
        if ($value) {
            return $value;
        }

        throw new RuntimeException('Required int value "'.$argumentId.'" is not passed or was empty', 1621861579);
    }

    private function validateArguments()
    {
        if (isset($_GET['cHash'])) {
            // link and authorisation handled by TYPO3.
            return;
        }

        if (isset($_GET['c'])) {
            $this->validateUrl();
        }
        // ok. No params

        if (isset($_GET['sh'])) {
            $hashParams = ['you' => GeneralUtility::_GP('you')];
            $this->validateSenderLinkHash($hashParams, GeneralUtility::_GP('sh'));
        }
    }

    private function setOnline(int $you, string $others)
    {
        $otherIds = GeneralUtility::intExplode(',', $others);
        $otherId = 0;
        foreach ($otherIds as $id) {
            if (!$id) {
                continue;
            }
            $otherId = $id;
        }

        $this->getOnlineRepository()->setOnline($you, $otherId);

        return 1;
    }

    private function getOtherIsOnline(int $you, $others): int
    {
        $otherIds = GeneralUtility::intExplode(',', $others);
        $otherId = 0;
        foreach ($otherIds as $id) {
            if (!$id) {
                continue;
            }
            $otherId = $id;
        }

        $onLineRecord = $this->getOnlineRepository()->getOnline($otherId);

        if (!$onLineRecord) {
            return OnlineRepository::ONLINE_NO;
        }

        $timeStamp = $onLineRecord['online'];
        $diff = (time() - $timeStamp);
        if ($diff > 300) {
            return OnlineRepository::ONLINE_NO;
        }
        if ((int) $onLineRecord['receiver_id'] !== $you) {
            return OnlineRepository::ONLINE_AWAY;
        }

        return OnlineRepository::ONLINE_YES;
    }

    private function checkForNewMessages(int $you): int
    {
        // Check when last seen the chat
        $lastOnlineRecord = $this->getOnlineRepository()->getOnline($you);
        $lastTimeStamp = $lastOnlineRecord['online'];

        return $this->getMessageRepository()->getMessageCountSince($you, $lastTimeStamp);
    }
}
