<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 25-5-2021 22:57
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

namespace BPN\BpnChat\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OnlineRepository
{
    const TABLE = 'tx_bpnchat_domain_model_online';
    const ONLINE_NO = 0;
    const ONLINE_YES = 1;
    const ONLINE_AWAY = -1;

    public function setOnline(int $userId, int $receiverId)
    {
        $table = self::TABLE;

        $currentState = $this->getOnline($userId);

        /** @var Connection $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);

        if (!$currentState) {
            $queryBuilder
                ->insert($table, ['user_id' => $userId, 'receiver_id' => $receiverId, 'online' => time()]);
        } else {
            $queryBuilder
                ->update($table, ['receiver_id' => $receiverId, 'online' => time()], ['user_id' => $userId]);
        }
    }

    public function getOnline(int $userId)
    {
        $table = self::TABLE;

        /** @var Connection $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);

        return $queryBuilder
            ->select(['*'], $table, ['user_id' => $userId])
            ->fetchAssociative();
    }

}
