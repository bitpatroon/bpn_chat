<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 29-4-2021 13:27
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

namespace BPN\BpnChat\Services;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class AuthorizationService
{
    public function isLoggedin()
    {
        return $this->hasUserSession(false);
    }

    private function hasUserSession(bool $allowAnonymous = false)
    {
        if (
            $GLOBALS['TSFE'] &&
            $GLOBALS['TSFE']->fe_user &&
            $GLOBALS['TSFE']->fe_user->user &&
            isset($GLOBALS['TSFE']->fe_user->user['uid']) &&
            $GLOBALS['TSFE']->fe_user->user['uid']) {
            if (!$allowAnonymous) {
                if (!isset($GLOBALS['TSFE']->fe_user->user['ses_anonymous']) || (int)$GLOBALS['TSFE']->fe_user->user['ses_anonymous'] == 0) {
                    return true;
                }
            } else {
                return true;
            }
        }

        return false;
    }

    public function getUserRecord()
    {
        if ($this->isLoggedin()) {
            return $GLOBALS['TSFE']->fe_user->user;
        }

        return [];
    }

    public function getUserField(string $fieldId) : ?string
    {
        $user = $this->getUserRecord();

        if ($user && isset($user[$fieldId])) {
            return $user[$fieldId];
        }

        return null;
    }

    public function getUserId() : int
    {
        return (int)($this->getUserField('uid') ?? 0);
    }

    /**
     * @param int $uid
     *
     * @return FrontendUser|null
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public function getFrontendUser(int $uid = 0)
    {
        $uid = $uid ?: $this->getUserId();
        if (!$uid) {
            return null;
        }
        /** @var FrontendUserRepository $frontendUserRepository */
        $frontendUserRepository = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(FrontendUserRepository::class);

        return $frontendUserRepository->findByUid($uid);
    }
}
