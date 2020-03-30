<?php
namespace Prefs\Model\Table;

use Cake\Database\Schema\TableSchemaInterface;
use Prefs\Model\Entity\Preference;
use Cake\Database\Schema\TableSchema;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Preferences Model
 *
 * @property \CakeDC\Users\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \Prefs\Model\Entity\Preference get($primaryKey, $options = [])
 * @method \Prefs\Model\Entity\Preference newEntity($data = null, array $options = [])
 * @method \Prefs\Model\Entity\Preference[] newEntities(array $data, array $options = [])
 * @method \Prefs\Model\Entity\Preference|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Prefs\Model\Entity\Preference saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Prefs\Model\Entity\Preference patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Prefs\Model\Entity\Preference[] patchEntities($entities, array $data, array $options = [])
 * @method \Prefs\Model\Entity\Preference findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PreferencesTable extends Table
{

    //<editor-fold desc="Core Baked Methods">
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config):void
    {
        parent::initialize($config);

        $this->setTable('preferences');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator):Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
//            ->scalar('prefs')
            ->requirePresence('prefs', 'create')
            ->notEmptyString('prefs');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules):RulesChecker
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }

    /**
     * Field prefs is json
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table definition fetched from database.
     * @return \Cake\Database\Schema\TableSchema the altered schema
     */
    protected function _initializeSchema(TableSchema $schema):TableSchemaInterface
    {
        $schema->setColumnType('prefs', 'json');

        return parent::_initializeSchema($schema);
    }
    //</editor-fold>

    /**
     * Get the Preference entity for one registered user
     *
     * The entity will be loaded with default values too, so
     * if the user has not set any personal prefs, the ojbect will
     * still be able to answer all prefs-related questions
     *
     * @param $user_id
     * @return Preference
     */
    public function getPreferencesFor($user_id)
    {
        $prefs = $this->find('all')
            ->where(['user_id' => $user_id])
            ->select(['id', 'user_id', 'prefs'])
            ->toArray();

        if (empty($prefs)) {
            $entity = new Preference([
                'id' => '',
                'user_id' => $user_id
            ]);
        } else {
            $entity = array_shift($prefs);
        }

        $entity->clean();
        return $entity;
    }

}
