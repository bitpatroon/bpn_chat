<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 11-5-2021 17:54
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

namespace BPN\BpnChat\Backend\UserFunctions;

use BPN\BpnChat\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class MessageTitle
{
    public function displayTitle(&$parameters)
    {
        $record = BackendUtility::getRecord($parameters['table'], $parameters['row']['uid']);

        $receiverList = $record['receivers'];
        $receivers = GeneralUtility::intExplode(',', $receiverList);

        if (!$record['sender']) {
            return;
        }

        $senderEmail = $this->getFrontEndUserRepository()->getEmail($record['sender']);
        $result = [$senderEmail];
        $receiver = $receivers ? $this->getFrontEndUserRepository()->getEmail(current($receivers)) : '';
        if ($receiver) {
            $result[] = $receiver;
        }

        $parameters['title'] .= substr(implode(' -> ', $result), 0, 60);
    }

    /**
     * @return FrontendUserRepository
     */
    public function getFrontEndUserRepository()
    {
        /* @var FrontendUserRepository $frontendUserRepository */
        return GeneralUtility::makeInstance(ObjectManager::class)
            ->get(FrontendUserRepository::class);
    }
}
