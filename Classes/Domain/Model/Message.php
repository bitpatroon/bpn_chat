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

namespace BPN\BpnChat\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

class Message extends AbstractEntity
{
    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
     */
    protected $sender;
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FrontendUser>
     */
    protected $receivers;

    /** @var string */
    protected $message;

    /** @var int */
    protected $delivered;

    /** @var int */
    protected $crdate;

    public function __construct()
    {
        $this->receivers = new ObjectStorage();
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
     */
    public function getSender()
    {
        return $this->sender;
    }

    public function setSender(\TYPO3\CMS\Extbase\Domain\Model\FrontendUser $sender) : Message
    {
        $this->sender = $sender;

        return $this;
    }

    public function addReceiver(?\TYPO3\CMS\Extbase\Domain\Model\FrontendUser $user)
    {
        if ($user) {
            $this->receivers->attach($user);
        }
    }

    public function removeReceiver(?\TYPO3\CMS\Extbase\Domain\Model\FrontendUser $user)
    {
        if (!$user) {
            return;
        }
        if ($this->receivers->contains($user)) {
            $this->receivers->detach($user);
        }
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FrontendUser>
     */
    public function getReceivers()
    {
        return $this->receivers;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUser[]|array|\TYPO3\CMS\Extbase\Persistence\ObjectStorage|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface $receivers
     */
    public function setReceivers($receivers) : Message
    {
        if ($receivers instanceof ObjectStorage) {
            $this->receivers = $receivers;

            return $this;
        }

        if ($receivers instanceof QueryResultInterface) {
            $receiversArray = $receivers->toArray();
        } elseif (is_array($receivers)) {
            $receiversArray = $receivers;
        }

        foreach ($receiversArray as $receiver) {
            $this->receivers->attach($receiver);
        }

        return $this;
    }

    public function getMessage() : string
    {
        return $this->message;
    }

    public function setMessage(string $message) : Message
    {
        $this->message = $message;

        return $this;
    }

    public function getDelivered() : int
    {
        return $this->delivered;
    }

    public function setDelivered(int $delivered) : Message
    {
        $this->delivered = $delivered;

        return $this;
    }

    public function getCrdate() : int
    {
        return $this->crdate ?? time();
    }
}
