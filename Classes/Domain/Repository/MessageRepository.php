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

namespace BPN\BpnChat\Domain\Repository;

use BPN\BpnChat\Traits\FrontEndUserTrait;
use BPN\BpnChat\Traits\NameServiceTrait;
use BPN\BpnChat\Traits\RepositoryTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

class MessageRepository extends Repository
{
    use FrontEndUserTrait;
    use NameServiceTrait;
    use RepositoryTrait;

    const TABLE = 'tx_bpnchat_domain_model_message';

    /**
     * @param int $userId
     * @param int $timeStamp must not be older than $timeStamp
     *
     * @return array
     */
    public function getChatPartnerIds(int $userId, int $timeStamp = 0)
    {
        $table = self::TABLE;
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $queryBuilder
            ->select('receivers')
            ->addSelectLiteral($queryBuilder->expr()->max('crdate', 'maxCrDate'))
            ->from($table)
            ->groupBy('receivers')
            ->where($queryBuilder->expr()->eq('sender', $userId));

        if($timeStamp){
            $queryBuilder->andWhere($queryBuilder->expr()->gt('crdate', $timeStamp));
        }

        $dataIsSender = $queryBuilder->execute()->fetchAllAssociative();

        $queryBuilder
            ->select('sender')
            ->addSelectLiteral($queryBuilder->expr()->max('crdate', 'maxCrDate'))
            ->from($table)
            ->groupBy('sender')
            ->where($queryBuilder->expr()->eq('receivers', $userId));
        if($timeStamp){
            $queryBuilder->andWhere($queryBuilder->expr()->gt('crdate', $timeStamp));
        }

        $dataIsReceiver = $queryBuilder->execute()->fetchAllAssociative();

        $rows = [];
        if ($dataIsSender) {
            foreach ($dataIsSender as $row) {
                $rows[(int) $row['receivers']] = (int) $row['maxCrDate'];
            }
        }
        if ($dataIsReceiver) {
            foreach ($dataIsReceiver as $row) {
                $rows[(int) $row['sender']] = (int) $row['maxCrDate'];
            }
        }

        arsort($rows);

        return $rows;
    }

    public function getLastMessages(array $userIds, array $otherUserIds, int $limit = 50)
    {
        $table = self::TABLE;

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $where = $this->getConditions($queryBuilder, $userIds, $otherUserIds);

        $queryBuilder
            ->select('*')
            ->from($table)
            ->where($where)
            ->orderBy('crdate')
            ->setMaxResults($limit);

        $rows = $queryBuilder->execute()->fetchAllAssociative();
        $this->setFullStatement($queryBuilder);

        $rows = $this->linkSenderReceivers($rows, $userIds, $otherUserIds);
        $rows = $this->setResultIndexField($rows);

        $this->markMyMessageDelivered($rows, $userIds);

        return $rows;
    }

    public function getNewMessages(array $userIds, array $otherUserIds, int $newerThanUid = 0)
    {
        $table = self::TABLE;
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $where = [];
        $where[] = $this->getConditions($queryBuilder, $userIds, $otherUserIds);
        if($newerThanUid){
            $where[] = $queryBuilder->expr()->gt('uid', $newerThanUid);
        }

        $queryBuilder
            ->select('*')
            ->from($table)
            ->where($queryBuilder->expr()->andX(...$where))
            ->orderBy('crdate');

        $rows = $queryBuilder->execute()->fetchAllAssociative();
        $rows = $this->linkSenderReceivers($rows, $userIds, $otherUserIds);
        $rows = $this->setResultIndexField($rows);

//        $this->markMyMessageDelivered($rows, $userIds);

        return $rows;
    }

    protected function linkSenderReceivers(array $rows, array $senderIds, array $receiverIds)
    {
        if (!$rows) {
            return [];
        }

        $userIds = $senderIds;
        if (!$userIds) {
            $userIds = [];
        }
        if ($receiverIds) {
            $userIds[] = array_merge($userIds, $receiverIds);
        }
        if (!$userIds) {
            return $rows;
        }

        /** @var array $users */
        $users = $this->getFrontEndUserRepository()->findAllAssociativeByUid(
            $userIds,
            'uid,pid,first_name,middle_name,last_name,email,username'
        );

        foreach ($users as &$user) {
            $user['name'] = $this->getNameService()->getFullName($user);
        }
        unset ($user);

        $adminUserRow = [
            'uid'      => 0,
            'pid'      => 0,
            'name'     => 'Admin',
            'email'    => '',
            'username' => '',
        ];

        $unknownUserRow = [
            'uid'      => 0,
            'pid'      => 0,
            'name'     => 'Unknown',
            'email'    => '',
            'username' => '',
        ];

        $result = [];
        foreach ($rows as $row) {
            $senderId = (int) $row['sender'];
            if ($senderId) {
                if (isset($users[$senderId])) {
                    $row['sender'] = $users[$senderId];
                } else {
                    $row['sender'] = $unknownUserRow;
                }
            } else {
                $row['sender'] = $adminUserRow;
            }

            if (!$row['receivers']) {
                $row['receivers'] = [$adminUserRow];
            } else {
                $receivers = [];
                $receiverIds = GeneralUtility::intExplode(',', ''.$row['receivers']);
                foreach ($receiverIds as $receiver) {
                    if (isset($users[$receiver])) {
                        $receivers[$receiver] = $users[$receiver];
                    } else {
                        $receivers[$receiver] = $unknownUserRow;
                    }
                }
                $row['receivers'] = $receivers;
            }

            $result[] = $row;
        }

        return $result;
    }

    protected function getConditions(
        QueryBuilder $queryBuilder,
        array $userIds,
        array $otherUserIds
    ): CompositeExpression {
        $constraintsUserAsSender = [];
        $constraintsUserAsReceiver = [];
        foreach ($userIds as $id) {
            $constraintsUserAsSender[] = $queryBuilder->expr()->eq('sender', $id);
            $constraintsUserAsReceiver[] = $queryBuilder->expr()->inset(
                'receivers',
                $queryBuilder->createNamedParameter($id, Connection::PARAM_STR)
            );
        }

        $constraintsOtherAsSender = [];
        $constraintsOtherAsReceiver = [];
        foreach ($otherUserIds as $id) {
            $constraintsOtherAsSender[] = $queryBuilder->expr()->eq('sender', $id);
            $constraintsOtherAsReceiver[] = $queryBuilder->expr()->inset(
                'receivers',
                $queryBuilder->createNamedParameter($id, Connection::PARAM_STR)
            );
        }

        // user is sender
        $userIsSender = $queryBuilder->expr()->andX(
            $queryBuilder->expr()->orX(...$constraintsUserAsSender),
            $queryBuilder->expr()->orX(...$constraintsOtherAsReceiver),
        );

        // user is receiver
        $userIsReceiver = $queryBuilder->expr()->andX(
            $queryBuilder->expr()->orX(...$constraintsOtherAsSender),
            $queryBuilder->expr()->orX(...$constraintsUserAsReceiver),
        );

        return $queryBuilder->expr()->orX($userIsSender, $userIsReceiver);
    }

    protected function markMyMessageDelivered(array $rows, array $userIds)
    {
        if (!$rows) {
            return;
        }

        krsort($rows);

        $uids = [];

        $removeMySendMessages = true;

        // remove my last message where other party has not seen them
        foreach ($rows as $key => $row) {
            if ($removeMySendMessages) {
                // find all older messages where I am the sender; remove!
                $sender = (int) $row['sender']['uid'];
                if (in_array($sender, $userIds)) {
                    // I am the sender, ignore
                    continue;
                }
            }
            $removeMySendMessages = false;
            $uids[$key] = $key;
        }

        if (!$uids) {
            return;
        }

        $table = self::TABLE;
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $queryBuilder
            ->update($table)
            ->set('delivered', 1, false, Connection::PARAM_INT)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY)
                ),
            )
            ->execute();
    }


    public function findBySender(int $userId)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('sender', $userId)
        );

        return $query->execute();
    }

    public function findByReceivers(int $userId)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->contains('receivers', $userId)
        );

        return $query->execute();
    }

}
