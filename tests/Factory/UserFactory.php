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

/**
 * UserFactory
 */
class UserFactory extends CakephpBaseFactory
{
    /**
     * Defines the Table Registry used to generate entities with
     * @return string
     */
    protected function getRootTableRegistryName(): string
    {
        return 'Users';
    }

    /**
     * Defines the default values of you factory. Usefull for
     * not nullable fields. You may use methods of the factory here
     * @return void
     */
    protected function setDefaultTemplate(): void
    {
        $this->setDefaultData(function(Generator $faker) {
            return [
                // set the model's default values
                // For example:
                // 'name' => $faker->lastName
            ];
        });
    }

    /**
     * @param array $parameter
     * @return UserFactory
     */
    public function withPeople(array $parameter = null): UserFactory
    {
        return $this->with('People', \App\Test\Factory\PrefsPersonFactory::make($parameter));
    }

    /**
    * @param array $parameter
    * @param int $n
    * @return UserFactory
    */
    public function withPreferences(array $parameter = null, int $n = 1): UserFactory
    {
        return $this
            ->with('Preferences', \App\Test\Factory\PreferenceFactory::make($parameter, $n)
            ->without('User'));
    }

}
