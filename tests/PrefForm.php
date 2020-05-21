<?php


namespace Prefs\Test;

use App\Lib\Prefs;
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

}
