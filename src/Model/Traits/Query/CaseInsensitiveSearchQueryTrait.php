<?php
declare(strict_types=1);

/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         4.9.1
 */
namespace App\Model\Traits\Query;

use Cake\ORM\Query\SelectQuery;

/**
 * Helper methods to perform case insensitive searches.
 * This is useful for the databases that are case-sensitive by default, i.e. Postgres.
 */
trait CaseInsensitiveSearchQueryTrait
{
    /**
     * @param \Cake\ORM\Query\SelectQuery $query Reference query object.
     * @param string $alias field to search along
     * @param string $string to be searched
     * @return \Cake\ORM\Query\SelectQuery
     */
    protected function searchCaseInsensitiveOnField(SelectQuery $query, string $alias, string $string): SelectQuery
    {
        return $query->where(["LOWER($alias) LIKE" => '%' . mb_strtolower($string) . '%']);
    }

    /**
     * @param \Cake\ORM\Query\SelectQuery $query Reference query object.
     * @param array<string> $aliases fields to search along
     * @param string $string to be searched
     * @return \Cake\ORM\Query\SelectQuery
     */
    protected function searchCaseInsensitiveOnMultipleFields(
        SelectQuery $query,
        array $aliases,
        string $string
    ): SelectQuery {
        $or = [];
        $value = '%' . mb_strtolower($string) . '%';
        foreach ($aliases as $alias) {
            $or[] = ["LOWER($alias) LIKE" => $value];
        }

        return $query->where(['OR' => $or]);
    }
}
