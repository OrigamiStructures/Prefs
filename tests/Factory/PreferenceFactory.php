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

use Faker\Generator;
use CakephpFixtureFactories\Factory\BaseFactory as CakephpBaseFactory;
use Cake\Utility\Hash;

/**
 * preferenceFactory
 */
class preferenceFactory extends CakephpBaseFactory
{
    /**
     * Defines the Table Registry used to generate entities with
     * @return string
     */
    protected function getRootTableRegistryName(): string
    {
        return 'Prefs.preferences';
    }

    /**
     * Defines the default values of you factory. Usefull for
     * not nullable fields. You may use methods of the factory here
     * @return void
     */
    protected function setDefaultTemplate(): void
    {
        $prefs = Hash::expand([
            'prefs.value' => 'variant-value-value',
            'prefs.nested.value' => 'variant-nested-value-value',
        ]);
        $this->setDefaultData(function(Generator $faker) use ($prefs) {
            return [
                'prefs' => $prefs,
                'user_id' => 1
            ];
        });
    }

    /**
     * @param array $parameter
     * @return preferenceFactory
     */
    public function withUsers(array $parameter = null): preferenceFactory
    {
        return $this->with('Users', UserFactory::make($parameter));
    }

}
