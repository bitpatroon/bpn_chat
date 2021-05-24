<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 11-5-2021 17:09
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

use BPN\BpnChat\Domain\Model\FrontendUser;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

class FrontendUserRepository extends \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
{
    // Example for repository wide settings
    public function initializeObject()
    {
        $querySettings = new Typo3QuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    public function findAllByUid(?array $chatPartnerIds)
    {
        if (!$chatPartnerIds) {
            return [];
        }

        return $this->getUsersByIds($chatPartnerIds);
    }

    public function getUsersByIds(array $uids): array
    {
        $query = $this->createQuery();
         $query->matching($query->in('uid', $uids));

        $data = $query->execute()->toArray();
        $result = [];
        if($data){
            /** @var FrontendUser $user */
            foreach($data as $user){
                $result[$user->getUid()] = $user;
            }
        }
        return $result;
    }

    /**
     * @param int[] $receiverIds
     *
     * @return array
     */
    public function getEmailFromReceivers(array $receiverIds)
    {
        $table = 'fe_users';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $queryBuilder
            ->select('uid', 'email')
            ->from($table)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($receiverIds, Connection::PARAM_INT_ARRAY)
                ),
            );

        $data = $queryBuilder->execute()->fetchAllAssociative();

        $result = [];
        if ($data) {
            foreach ($data as $row) {
                $result[(int) $row['uid']] = $row['email'];
            }
        }

        return $result;
    }

    /**
     * @param int[] $receiverIds
     *
     * @return string
     */
    public function getEmail(int $userId)
    {
        //$table = self::TABLE;
        $table = 'fe_users';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $queryBuilder
            ->select('email')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT)),
            );

        // retrieve all (or fetchAllAssociative, fetchFirstColumn)
        $data = $queryBuilder->execute()->fetchAssociative();
        if ($data) {
            return $data['email'];
        }

        return '';
    }
}
