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
 * @since         4.10.0
 */
namespace Passbolt\Metadata\Service;

use App\Error\Exception\CustomValidationException;
use App\Utility\UserAccessControl;
use Cake\Event\EventDispatcherTrait;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;
use Passbolt\Metadata\Model\Dto\MetadataKeyCreateDto;
use Passbolt\Metadata\Model\Entity\MetadataKey;

class MetadataKeyCreateService
{
    use EventDispatcherTrait;
    use LocatorAwareTrait;

    public const AFTER_METADATA_KEY_CREATE_SUCCESS_EVENT_NAME = 'MetadataKey.afterKeyCreate.success';

    /**
     * @param \App\Utility\UserAccessControl $uac UAC.
     * @param \Passbolt\Metadata\Model\Dto\MetadataKeyCreateDto $dto DTO.
     * @return \Passbolt\Metadata\Model\Entity\MetadataKey
     */
    public function create(UserAccessControl $uac, MetadataKeyCreateDto $dto): MetadataKey
    {
        $uac->assertIsAdmin();

        // Set created and modified by on both key and relations
        $data = $dto->toArray() + [
            'created_by' => $uac->getId(),
            'modified_by' => $uac->getId(),
        ];
        foreach ($data['metadata_private_keys'] as $i => $value) {
            $data['metadata_private_keys'][$i]['created_by'] = $uac->getId();
            $data['metadata_private_keys'][$i]['modified_by'] = $uac->getId();
        }

        $metadataKey = $this->buildAndSaveEntity($data);

        $this->dispatchEvent(
            static::AFTER_METADATA_KEY_CREATE_SUCCESS_EVENT_NAME,
            compact('metadataKey', 'uac'),
            $this
        );

        return $metadataKey;
    }

    /**
     * @param array $data user provided data
     * @return \Passbolt\Metadata\Model\Entity\MetadataKey
     * @throws \App\Error\Exception\CustomValidationException if metadata key data cannot be validated.
     * @throws \Cake\Http\Exception\InternalErrorException if metadata key cannot be saved due to internal issues.
     */
    public function buildAndSaveEntity(array $data): MetadataKey
    {
        /** @var \Passbolt\Metadata\Model\Table\MetadataKeysTable $metadataKeysTable */
        $metadataKeysTable = $this->fetchTable('Passbolt/Metadata.MetadataKeys');

        $metadataKey = $metadataKeysTable->newEntity($data, [
            'accessibleFields' => [
                'armored_key' => true,
                'fingerprint' => true,
                'metadata_private_keys' => true,
                'created_by' => true,
                'modified_by' => true,
            ],
            'associated' => [
                'MetadataPrivateKeys' => [
                    'accessibleFields' => [
                        'user_id' => true,
                        'data' => true,
                        'created_by' => true,
                        'modified_by' => true,
                    ],
                ],
            ],
        ]);
        try {
            /** @var \Passbolt\Metadata\Model\Entity\MetadataKey $result */
            $result = $metadataKeysTable->saveOrFail($metadataKey);
        } catch (PersistenceFailedException $e) {
            $errors = $e->getEntity()->getErrors();

            throw new CustomValidationException(
                __('The metadata key could not be saved.'),
                $errors
            );
        } catch (Exception $e) { // @phpstan-ignore-line
            throw new InternalErrorException(__('Could not save the metadata key, please try again later.'), null, $e);
        }

        return $result;
    }
}
