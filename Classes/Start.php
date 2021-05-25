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

use BPN\BpnChat\Domain\Repository\MessageRepository;
use BPN\BpnChat\Traits\AuthorizationServiceTrait;
use BPN\BpnChat\Traits\LanguageTrait;
use BPN\BpnChat\Traits\SecureLinkTrait;
use RuntimeException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class Start
{
    use AuthorizationServiceTrait;
    use LanguageTrait;
    use SecureLinkTrait;

    const FAILURE = 'failure';

    public function process()
    {
        $operation = GeneralUtility::_GP('operation');
        $debug = GeneralUtility::_GP('debug');


        // todo add checksum check on links!
        $this->validateArguments();

        try {
            switch ($operation) {
                case 'getnew':
                    $you = $this->getRequiredArgument('you');
                    $others = $this->getRequiredArgument('other');

                    return $this->getNewChatMessages($you, $others);

                case 'get':
                    $you = $this->getRequiredArgument('you');
                    $others = $this->getRequiredArgument('other');

                    return $this->getLastMessages($you, $others);
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

    private function getNewChatMessages(string $userId, string $others): JsonResponse
    {
        /** @var MessageRepository $messageRepository */
        $messageRepository = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(MessageRepository::class);

        $senderIds = GeneralUtility::intExplode(',', $userId);
        $otherUserIds = GeneralUtility::intExplode(',', $others);

        $result = [];
        $result['messages'] = $messageRepository->getNewMessages($senderIds, $otherUserIds);
        $result['count'] = count($result['messages']);

        return new JsonResponse($result);
    }

    private function getLastMessages(string $userId, string $others): JsonResponse
    {
        /** @var MessageRepository $messageRepository */
        $messageRepository = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(MessageRepository::class);

        $senderIds = GeneralUtility::intExplode(',', $userId);
        $otherUserIds = GeneralUtility::intExplode(',', $others);

        $result = [];
        $result['messages'] = $messageRepository->getLastMessages($senderIds, $otherUserIds);
        $result['count'] = count($result['messages']);

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
        throw new \RuntimeException('Required value "'.$argumentId.'" is not passed or was empty', 1621860676);
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

        throw new \RuntimeException('Required int value "'.$argumentId.'" is not passed or was empty', 1621861579);
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

    }
}
