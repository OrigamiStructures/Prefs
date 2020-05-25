<?php
declare(strict_types=1);

/**
 *
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Juan Pablo Ramirez
 * @author        Nicolas Masson
 * @link          https://github.com/pakacuda/cakephp-fixture-factories
 * @since         1.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Prefs\Test\Factory;

use Cake\Utility\Hash;
use Faker\Generator;
use CakephpFixtureFactories\Factory\BaseFactory as CakephpBaseFactory;

/**
 * preferenceFactory
 */
class PreferenceFactory extends CakephpBaseFactory
{
    /**
     * Defines the Table Registry used to generate entities with
     * @return string
     */
    protected function getRootTableRegistryName(): string
    {
        return 'preferences';
    }

    /**
     * Defines the default values of you factory. Usefull for
     * not nullable fields. You may use methods of the factory here
     * @return void
     */
    protected function setDefaultTemplate(): void
    {
        var_export('something');
        $prefs = Hash::expand([
            'prefs.value' => 'value-value',
            'prefs.nested.value' => 'nested-value-value',
        ]);
        $prefs = json_encode($prefs);
        $this->setDefaultData(function(Generator $faker) use ($prefs) {
            return [
                'prefs' => $prefs,
                'user_id' => 1
            ];
        });
    }

    /**
     * @param array $parameter
     * @return PrefsPersonFactory
     */
    public function withUser(array $parameter = null): PrefsPersonFactory
    {
        return $this->with('User', \Prefs\Test\Factory\UserFactory::make($parameter));
    }

}
