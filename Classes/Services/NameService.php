<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 24-5-2021 22:12
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

use BPN\BpnChat\Domain\Model\FrontEndUser;
use BPN\BpnChat\Traits\FrontEndUserTrait;

final class NameService
{
    use FrontEndUserTrait;

    /**
     * @param array|int|FrontEndUser $user
     */
    public function getFullName($user, bool $defaultWhenNoName = true): string
    {
        if ($user instanceof FrontEndUser) {
            return $this->getFullNameByFrontEndUser($user);
        }

        if (is_array($user)) {
            return $this->getFullNameByArray($user, $defaultWhenNoName);
        }

        if (is_int($user)) {
            $userRecord = $this->getFrontEndUserRepository()->getUserRecordByUid($user);
            if ($userRecord) {
                return $this->getFullNameByArray($userRecord, $defaultWhenNoName);
            }

            if ($defaultWhenNoName) {
                return $this->getUserUnkown($user);
            }

            return '';
        }

        if ($defaultWhenNoName) {
            return $this->getUserUnkown(0);
        }

        return '';
    }

    private function getFullNameByArray(array $userRecord, bool $defaultWhenNoName = true)
    {
        if (!$userRecord) {
            if ($defaultWhenNoName) {
                return $this->getUserUnkown(0);
            }

            return '';
        }

        $result = [
            0   => $userRecord['first_name'],
            100 => $userRecord['last_name'],
        ];

        if ($userRecord['middle_name']) {
            $result[50] = $userRecord['middle_name'];
        }

        ksort($result);
        $result = implode(' ', $result);
        $result = trim($result);
        if ($result) {
            return $result;
        }

        if (!$defaultWhenNoName) {
            return '';
        }

        return $this->getUserUnkown((int) $userRecord['uid']);
    }

    private function getFullNameByFrontEndUser(FrontEndUser $frontEndUser)
    {
        $userRecord = [
            'first_name'  => $frontEndUser->getFirstName(),
            'middle_name' => $frontEndUser->getMiddleName(),
            'last_name'   => $frontEndUser->getFirstName(),
        ];

        return $this->getFullNameByArray($userRecord);
    }

    public function getUserUnkown(int $uid)
    {
        return sprintf('[user: %s]', $uid);
    }
}
