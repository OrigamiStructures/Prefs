<?php


namespace Prefs\Test;

use App\Lib\Prefs;
use Cake\Event\EventManager;
use Cake\Form\Schema;
use Cake\Validation\Validator;
use Prefs\Lib\PrefsBase;

class PrefForm extends PrefsBase
{
    /**
     * @var bool|Prefs
     */
    public $Prefs = false;

    public $prefsSchema = [
        'prefs' => [
            'type' => 'json',
            'default' => [
                'value' => 'value-value',
                'nested.value' => 'nested-value-value',
            ],
        ],
    ];
    public function __construct(EventManager $eventManager = null)
    {
        parent::__construct($eventManager);
        return $this;
    }

    /**
     * @param Validator $validator
     * @return Validator
     */
    public function validationDefault(Validator $validator):Validator
    {
        return parent::validationDefault($validator);
    }

    protected function _buildSchema(Schema $schema):Schema
    {
        return parent::_buildSchema($schema);
    }

}
