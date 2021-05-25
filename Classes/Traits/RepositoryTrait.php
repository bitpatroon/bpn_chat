<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 21-5-2021 13:22
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

namespace BPN\BpnChat\Traits;

use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

trait RepositoryTrait
{
    /** @var array */
    protected $sql;

    /**
     * @return array
     */
    public function getSql() : array
    {
        return $this->sql;
    }

    protected function setResultIndexField(array $data, string $field = 'uid')
    {
        if (empty($data)) {
            return [];
        }

        $rows = [];
        if ($data) {
            foreach ($data as $row) {
                if ($row instanceof AbstractEntity) {
                    switch ($field) {
                        case 'uid':
                            $fieldValue = $row->getUid();
                            break;
                        case 'pid':
                            $fieldValue = $row->getPid();
                            break;
                        default:
                            $fieldValue = $row->_getProperty($field);
                            break;
                    }
                } elseif (isset($row[$field])) {
                    $fieldValue = $row[$field];
                }

                if (is_numeric($fieldValue)) {
                    $fieldValue = (int)$fieldValue;
                }

                $rows[$fieldValue] = $row;
            }
        }

        return $rows;
    }

    protected function getQuery(QueryBuilder $builder) : array
    {
        return $builder->getQueryParts();
    }

    protected function getFullStatement(QueryBuilder $queryBuilder) : array
    {
        return [
            'statement'  => $this->getSqlStatement($queryBuilder),
            'parameters' => $this->getSqlParameters($queryBuilder),
            'parts'      => $this->getQuery($queryBuilder)
        ];
    }

    protected function getSqlStatement(QueryBuilder $queryBuilder) : string
    {
        return $queryBuilder->getSQL();
    }

    protected function getSqlParameters(QueryBuilder $queryBuilder) : array
    {
        return $queryBuilder->getParameters();
    }

    protected function setFullStatement(QueryBuilder $queryBuilder){
        $this->sql = $this->getFullStatement($queryBuilder);
    }
}
