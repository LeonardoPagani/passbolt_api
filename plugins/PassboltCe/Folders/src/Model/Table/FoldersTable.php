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

namespace Passbolt\Folders\Model\Table;

use App\Model\Validation\ArmoredMessage\IsParsableMessageValidationRule;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Passbolt\Folders\Model\Behavior\FolderizableBehavior;
use Passbolt\Folders\Model\Entity\Folder;
use Passbolt\Folders\Model\Traits\Folders\FoldersFindersTrait;
use Passbolt\Metadata\Model\Dto\MetadataFolderDto;
use Passbolt\Metadata\Model\Rule\IsFolderV5ToV4DowngradeAllowedRule;
use Passbolt\Metadata\Model\Rule\IsMetadataKeyTypeAllowedBySettingsRule;
use Passbolt\Metadata\Model\Rule\IsMetadataKeyTypeSharedOnSharedItemRule;
use Passbolt\Metadata\Model\Rule\IsSharedMetadataKeyUniqueActiveRule;
use Passbolt\Metadata\Model\Rule\IsV4ToV5UpgradeAllowedRule;
use Passbolt\Metadata\Model\Rule\IsValidEncryptedMetadataRule;
use Passbolt\Metadata\Model\Rule\MetadataKeyIdExistsInRule;
use Passbolt\Metadata\Model\Rule\MetadataKeyIdNotExpiredRule;

/**
 * Folders Model
 *
 * @method \Passbolt\Folders\Model\Entity\Folder get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Passbolt\Folders\Model\Entity\Folder newEntity(array $data, array $options = [])
 * @method \Passbolt\Folders\Model\Entity\Folder[] newEntities(array $data, array $options = [])
 * @method \Passbolt\Folders\Model\Entity\Folder|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Passbolt\Folders\Model\Entity\Folder saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Passbolt\Folders\Model\Entity\Folder patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Passbolt\Folders\Model\Entity\Folder[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Passbolt\Folders\Model\Entity\Folder findOrCreate($search, ?callable $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @mixin \Passbolt\Folders\Model\Behavior\FolderizableBehavior
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\HasOne $Creator
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\HasOne $Modifier
 * @property \App\Model\Table\PermissionsTable&\Cake\ORM\Association\HasOne $Permission
 * @property \App\Model\Table\PermissionsTable&\Cake\ORM\Association\HasMany $Permissions
 * @property \Passbolt\Folders\Model\Table\FoldersRelationsTable&\Cake\ORM\Association\HasMany $FoldersRelations
 * @property \Passbolt\Folders\Model\Table\FoldersTable&\Cake\ORM\Association\BelongsToMany $ChildrenFolders
 * @property \App\Model\Table\ResourcesTable&\Cake\ORM\Association\BelongsToMany $ChildrenResources
 * @property \Passbolt\Folders\Model\Table\FoldersHistoryTable&\Cake\ORM\Association\BelongsTo $FoldersHistory
 * @method \Passbolt\Folders\Model\Entity\Folder newEmptyEntity()
 * @method iterable<\Passbolt\Folders\Model\Entity\Folder>|iterable<\Cake\Datasource\EntityInterface>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\Passbolt\Folders\Model\Entity\Folder>|iterable<\Cake\Datasource\EntityInterface> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\Passbolt\Folders\Model\Entity\Folder>|iterable<\Cake\Datasource\EntityInterface>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\Passbolt\Folders\Model\Entity\Folder>|iterable<\Cake\Datasource\EntityInterface> deleteManyOrFail(iterable $entities, $options = [])
 * @method  \Cake\ORM\Query\SelectQuery findById(string $id)
 */
class FoldersTable extends Table
{
    use FoldersFindersTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('folders');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior(TimestampBehavior::class);
        $this->addBehavior(FolderizableBehavior::class);

        $this->hasOne('Creator', [
            'className' => 'Users',
            'bindingKey' => 'created_by',
            'foreignKey' => 'id',
        ]);
        $this->hasOne('Modifier', [
            'className' => 'Users',
            'bindingKey' => 'modified_by',
            'foreignKey' => 'id',
        ]);
        $this->hasOne('Permission', [
            'className' => 'Permissions',
            'foreignKey' => 'aco_foreign_key',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('Permissions', [
            'foreignKey' => 'aco_foreign_key',
            'dependent' => false,
        ]);
        $this->hasMany('Passbolt/Folders.FoldersRelations', [
            'className' => 'Passbolt/Folders.FoldersRelations',
            'foreignKey' => 'foreign_id',
            'dependent' => false,
        ]);
        $this->belongsToMany('Passbolt/Folders.ChildrenFolders', [
            'className' => 'Passbolt/Folders.Folders',
            'targetForeignKey' => 'foreign_id',
            'foreignKey' => 'folder_parent_id',
            'through' => 'Passbolt/Folders.FoldersRelations',
            'dependent' => false,
            'conditions' => [
                'FoldersRelations.foreign_model' => 'Folder',
            ],
        ]);
        $this->belongsToMany('ChildrenResources', [
            'className' => 'Resources',
            'targetForeignKey' => 'foreign_id',
            'foreignKey' => 'folder_parent_id',
            'through' => 'Passbolt/Folders.FoldersRelations',
            'dependent' => false,
            'conditions' => [
                'FoldersRelations.foreign_model' => 'Resource',
            ],
        ]);
        $this->belongsTo('MetadataKeys', [
            'className' => 'Passbolt/Metadata.MetadataKeys',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id', __('The identifier should be a valid UUID.'))
            ->allowEmptyString('id', __('The identifier should not be empty.'), 'create');

        $validator
            ->utf8Extended('name', __('The name should be a valid UTF8 string.'))
            ->maxLength(
                'name',
                Folder::MAX_NAME_LENGTH,
                __('The name length should be maximum {0} characters.', Folder::MAX_NAME_LENGTH)
            )
            ->requirePresence('name', 'create', __('A name is required.'))
            ->allowEmptyString('name', __('The name should not be empty.'), false);

        $validator
            ->uuid('created_by', __('The identifier of the user who created the folder should be a valid UUID.'))
            ->requirePresence(
                'created_by',
                'create',
                __('The identifier of the user who created the folder is required.')
            )
            ->notEmptyString(
                'created_by',
                __('The identifier of the user who created the folder should not be empty.'),
                false
            );

        $validator
            ->uuid('modified_by', __('The identifier of the user who modified the folder should be a valid UUID.'))
            ->requirePresence(
                'modified_by',
                'create',
                __('The identifier of the user who modified the folder is required.')
            )
            ->notEmptyString(
                'modified_by',
                __('The identifier of the user who modified the folder should not be empty.'),
                false
            );

        return $validator;
    }

    /**
     * V5 validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationV5(Validator $validator): Validator
    {
        $validator = $this->validationDefault($validator);

        // Remove all validation on the v4 meta properties
        // Enforce all v4 fields to be empty
        foreach (MetadataFolderDto::V4_META_PROPS as $v4Fields) {
            $validator->remove($v4Fields);
        }

        /**
         * V5 fields validations.
         */
        $validator
            ->uuid('metadata_key_id', __('The metadata key ID should be a valid UUID.'))
            ->allowEmptyString('metadata_key_id');

        $validator
            ->ascii('metadata', __('The metadata should be a valid ASCII string.'))
            ->requirePresence('metadata', 'create', __('An armored key is required.'))
            ->notEmptyString('metadata', __('The metadata should not be empty.'))
            ->add('metadata', 'isMetadataParsable', new IsParsableMessageValidationRule());

        $validator
            ->utf8Extended('metadata_key_type', __('The metadata key type should be a valid UTF8 string.'))
            ->allowEmptyString('metadata_key_type')
            ->inList('metadata_key_type', ['user_key', 'shared_key'], __(
                'The metadata key type should be one of the following: {0}.',
                implode(', ', ['user_key', 'shared_key'])
            ));

        return $validator;
    }

    /**
     * Rule checker.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->addUpdate(new IsFolderV5ToV4DowngradeAllowedRule(), 'v5_to_v4_downgrade_allowed', [
            'errorField' => 'name',
            'message' => __('The settings selected by your administrator prevent from downgrading folder.'),
        ]);

        return $rules;
    }

    /**
     * Rule checker for v5 properties
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRulesV5(RulesChecker $rules): RulesChecker
    {
        $rules->add(new IsMetadataKeyTypeAllowedBySettingsRule(), 'isMetadataKeyTypeAllowedBySettings', [
            'errorField' => 'metadata_key_type',
            'message' => __('The settings selected by your administrator prevent from using that key type.'),
        ]);

        $rules->add(new MetadataKeyIdExistsInRule(), 'metadata_key_exists', [
            'errorField' => 'metadata_key_id',
            'message' => __('The metadata key does not exist.'),
        ]);

        $rules->add(new MetadataKeyIdNotExpiredRule(), 'isMetadataKeyNotExpired', [
            'errorField' => 'metadata_key_id',
            'message' => __('The metadata key is marked as expired.'),
        ]);

        $rules->add(new IsSharedMetadataKeyUniqueActiveRule(), 'isSharedMetadataKeyUniqueActive', [
            'errorField' => 'metadata_key_id',
            'message' => __('The shared metadata key should be unique.'),
        ]);

        $rules->add(new IsValidEncryptedMetadataRule(), 'isValidEncryptedMetadata', [
            'errorField' => 'metadata',
            'message' => __('The folder metadata provided cannot be decrypted.'),
        ]);

        $rules->addUpdate(
            new IsMetadataKeyTypeSharedOnSharedItemRule(),
            'isMetadataKeyTypeSharedOnSharedItem',
            [
                'errorField' => 'metadata_key_type',
                'message' => __('A folder of type personal cannot be shared with other users or a group.'),
            ]
        );

        $rules->addUpdate(new IsV4ToV5UpgradeAllowedRule(), 'v4_to_v5_upgrade_allowed', [
            'errorField' => 'metadata',
            'message' => __('The settings selected by your administrator prevent from upgrading v4 to v5.'),
        ]);

        return $rules;
    }

    /**
     * Get a folder created date.
     *
     * @param string $id The folder id
     * @return string
     */
    public function getCreatedDate(string $id): string
    {
        return $this->findById($id)
            ->select('created')
            ->first()
            ->get('created');
    }
}
