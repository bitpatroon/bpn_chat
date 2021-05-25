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

use BPN\BpnChat\Traits\BpnChatConfigurationTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MessageService
{
    use BpnChatConfigurationTrait;

    /**
     * @param string|int|array $ids
     *
     * @return array
     */
    public function getUserIds($ids)
    {
        $userIds = [];
        if (!is_array($ids)) {
            $ids = GeneralUtility::intExplode(',', (string) $ids);
        }

        foreach ($ids as $id) {
            $id = (int) $id;
            $userIds[$id] = $id;
            if ($this->isAdmin($id)) {
                $userIds[0] = 0;
            }
        }

        return $userIds;
    }

    private function isAdmin(int $userId)
    {
        $receiverIds = $this->bpnChatConfiguration->getReceiverIds();

        return in_array($userId, $receiverIds);
    }
}
