<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Prefs\Test;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Prefs\Controller\Component\PreferencesComponent;
use Prefs\Exception\UnknownPreferenceKeyException;
use Prefs\Lib\PrefsBase;
use Prefs\Test\Factory\PrefsPersonFactory;

class PreferenceEntityTest extends TestCase
{

    /**
     * @var PreferencesComponent
     */
    public $Component;

    public $fixtures = [
        'app.people',
        'app.users',
    ];

    public function setUp() : void
    {
        $request = $this->createMock(ServerRequest::class);
        $controller = new Controller($request);
        $registry = new ComponentRegistry($controller);
        $this->Component = new PreferencesComponent($registry, [
            'concretePrefsForm' => PrefForm::class,
            'prefsWrapper' => PrefsBase::class,
            'linkId' => 1,
        ]);
        PrefsPersonFactory::make(1)
            ->withUser()
            ->persist();
    }

    public function testEntityFor()
    {
        PrefsPersonFactory::make(1)
            ->withUser()
            ->persist();
        $prefs = $this->Component
            ->getPrefs()
            ->getEntity();

        $this->assertEquals('value-value', $prefs->for('value'));
        $this->assertEquals('nested-value-value', $prefs->for('nested.value'));

        $this->expectException(UnknownPreferenceKeyException::class);
        $prefs->for('bad_path');

    }

    public function testEntitySetVariant()
    {
        PrefsPersonFactory::make(1)
            ->withUser()
            ->persist();
        $prefs = $this->Component
            ->getPrefs()
            ->getEntity();

        $prefs->setVariant('value', 'new value of value');
        $this->assertEquals('new value of value', $prefs->for('value'),
            'a valid change value could not be inserted in the variant list');

        /**
         * arbitraty, invalid insertions are allowed
         */
        $prefs->setVariant('insert.value', 'new value of value');
        $result = $prefs->getVariants()['prefs']['insert']['value'];
        $this->assertEquals('new value of value', $result,
            'An arbitrary invalid node could not be added to the variant list');

    }

    public function testEntityGetVariant()
    {
        PrefsPersonFactory::make(1)
            ->withUser()
            ->persist();
        $prefs = $this->Component
            ->getPrefs()
            ->getEntity();

        $prefs->setVariant('value', 'new value of value');
        $this->assertEquals('new value of value', $prefs->getVariant('value'));
        $this->assertEquals(null, $prefs->getVariant('nested.value'));

    }

    public function testEntityGetDefaults()
    {
        PrefsPersonFactory::make(1)
            ->withUser()
            ->persist();
        $prefs = $this->Component
            ->getPrefs()
            ->getEntity();

        $expected = [
            'prefs.value' => 'value-value',
            'prefs.nested.value' => 'nested-value-value',
        ];

        $this->assertEquals($expected, $prefs->getDefaults());

    }

}
