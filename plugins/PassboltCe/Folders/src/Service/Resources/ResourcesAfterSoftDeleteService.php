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
 * @since         2.13.0
 */

namespace Passbolt\Folders\Service\Resources;

use App\Model\Entity\Resource;
use Cake\ORM\TableRegistry;

class ResourcesAfterSoftDeleteService
{
    /**
     * @param \App\Model\Entity\Resource $resource The soft deleted resource.
     * @return void
     * @throws \Exception
     */
    public function afterSoftDelete(Resource $resource): void
    {
        TableRegistry::getTableLocator()
            ->get('Passbolt/Folders.FoldersRelations')
            ->deleteAll(['FoldersRelations.foreign_id' => $resource->id]);
    }
}
