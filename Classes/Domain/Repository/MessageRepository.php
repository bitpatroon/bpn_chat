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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class MessageRepository extends Repository
{
    public function getChatPartnerIds(int $userId)
    {
        // see https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ApiOverview/Database/ExpressionBuilder

        /** Connection $connection */
        $connection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_bpnchat_domain_model_message');

        // messages where userid the sender
        $dataIsSender = $connection
            ->select(
                ['receivers'],
                'tx_bpnchat_domain_model_message',
                ['sender' => $userId, 'delivered' => 0],
                ['receivers']
            )
            ->fetchAllAssociative();

        $dataIsReceiver = $connection
            ->select(
                ['sender'],
                'tx_bpnchat_domain_model_message',
                ['receivers' => $userId, 'delivered' => 0],
                ['sender']
            )
            ->fetchAllAssociative();

        // messages where I am the receiver

        $rows = [];
        if ($dataIsSender) {
            foreach ($dataIsSender as $row) {
                $rows[(int)$row['receivers']] = (int)$row['receivers'];
            }
        }
        if ($dataIsReceiver) {
            foreach ($dataIsReceiver as $row) {
                $rows[(int)$row['sender']] = (int)$row['sender'];
            }
        }

        return $rows;
    }

    public function getNewMessages(int $userId, array $other = [])
    {
        $query = $this->createQuery();

        // user is sender
        $constraintsUserIsSender = [
            $query->equals('sender', $userId)
        ];
        $constraintsUserIsReceiver = [
            $query->contains('receivers', $userId)
        ];

        $constraintsOtherIsReceiver = [];
        $constraintsOtherIsSender = [];
        if ($other) {
            foreach ($other as $item) {
                $constraintsOtherIsReceiver[] = $query->contains('receivers', $item);
                $constraintsOtherIsSender[] = $query->equals('sender', $item);
            }
        }

        $constraintsUserIsSender[] = $query->logicalOr($constraintsOtherIsReceiver);
        $constraintsUserIsReceiver[] = $query->logicalOr($constraintsOtherIsSender);

        // all!
        $query
            ->matching(
                $query->logicalOr(
                    [
                        $query->logicalAnd($constraintsUserIsSender),
                        $query->logicalAnd($constraintsUserIsReceiver)
                    ]
                )
            );

        $query->setOrderings(['crdate' => QueryInterface::ORDER_ASCENDING]);

        return $query->execute()->toArray();
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
