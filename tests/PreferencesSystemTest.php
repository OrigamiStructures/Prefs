<?php /** @noinspection ALL */

namespace Prefs\Test;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Prefs\Controller\Component\PreferencesComponent;
use Prefs\Exception\UnknownPreferenceKeyException;
use Prefs\Lib\PrefsBase;
use Prefs\Model\Entity\Preference;
use Prefs\Test\PrefForm;
use Prefs\Test\Factory\PrefsPersonFactory;
use Prefs\Test\Factory\PreferenceFactory;

class PreferencesSystemTest extends TestCase
{

    /**
     * @var PreferencesComponent
     */
    public $Component;

//    public $fixtures = [
////        'Prefs\Test\Fixture\PreferencesFixture',
//        'app.people',
//        'app.users',
//    ];

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

    public function tearDown() : void
    {
        $t = TableRegistry::getTableLocator()->get('Preferences')
            ->deleteAll([1 => 1]);
        unset($this->Component);
    }

    //<editor-fold desc="WRAPPER TESTS">
    public function testConstruction()
    {
        PrefsPersonFactory::make(1)
            ->withUser()
            ->persist();

        $this->assertInstanceOf(PreferencesComponent::class, $this->Component);

        $Prefs = $this->Component->getPrefs();
        $this->assertInstanceOf(PrefsBase::class, $Prefs);

        $Entity = $Prefs->getEntity();
        $this->assertInstanceOf(Preference::class, $Entity);

        $Form = $Prefs->getForm();
        $this->assertInstanceOf(PrefForm::class, $Form);

        $this->assertEquals(1, $this->Component->getConfig('linkId'));
        $this->Component->setConfig('linkId', 2);
        $this->assertEquals(2, $this->Component->getConfig('linkId'));

    }
    public function testWrapperFor()
    {
        PrefsPersonFactory::make(1)
            ->withUser()
            ->persist();
        $prefs = $this->Component
            ->getPrefs();

        $this->assertEquals('value-value', $prefs->for('value'),
            'Form::for() did not return expected simple value');
        $this->assertEquals('nested-value-value', $prefs->for('nested.value'),
            'Form::for() did not return expected nested value');
        //failures
        $this->expectException(UnknownPreferenceKeyException::class);
        $this->assertEquals(null, $prefs->for('bad.path'),
            'Form::for() did not return null for unknown path');
    }
    //</editor-fold>

    /**
     * When the schema changes, the next time the entity loads it changes
     *
     * The entity will drop any entries that are no longer included in the
     * schema and any entries that are now set to a default value.
     *
     */
    public function testSchemaChangeEffectsEntity()
    {
        PrefsPersonFactory::make(1)
            ->withUser()
            ->persist();
        PreferenceFactory::make(['prefs' => [
            'prefs.value' => 'variant-value-value', //variant should persist
            'prefs.nested.value' => 'nested-value-value', //default matched value should evaporate
            'prefs.expired.path' => 'expired-value' //path not in schema should evaporate
        ]])
            ->persist();
        $prefs = $this->Component
            ->getPrefs(1)
            ->getEntity();

        $expected = ['prefs' => ['value' => 'variant-value-value']];
        $this->assertEquals($expected, $prefs->getVariants());
    }

    public function testSetPrefs()
    {
        //<editor-fold desc="PREPARE DATA AND ENVIRONEMNT">
        PrefsPersonFactory::make(1)
            ->withUser()
            ->persist();
        PreferenceFactory::make(['prefs' => []])
            ->persist();
        $prefs = $this->Component
            ->getPrefs(1)
            ->getEntity();
        //</editor-fold>

        $this->assertEquals([], $prefs->getVariants(),
            'Test did not set up properly');

        /* Try saving one new value to the entity */
        //<editor-fold desc="CONFIGURE STUB REQUEST">
        $postRequest = $this->createStub(ServerRequest::class);
        $postRequest
            ->method('getData')
            ->willReturn([
                'prefs' => [
                    'value' => 'variant-value',
                ]
            ]);
        $postRequest->method('is')->willReturn(true);
        $this->Component->getController()->setRequest($postRequest);
        //</editor-fold>

        $result = $this->Component->setPrefs();

        $this->isInstanceOf(PrefsBase::class, $result,
            'A valid setPrefs request did not return the expected object');

        $expected = ['prefs' => ['value' => 'variant-value',]];
        $this->assertEquals($expected, $result->getEntity()->getVariants(),
            'A valid setPrefs request did not create the entity variant expected');

        /** Try saving another value to the same entity */
        //<editor-fold desc="CONFIGURE STUB REQUEST">
        $postRequest = $this->createStub(ServerRequest::class);
        $postRequest
            ->method('getData')
            ->willReturn([
                'prefs' => [
                    'nested' =>
                        ['value' => 'variant-value'],
                ]
            ]);
        $postRequest->method('is')->willReturn(true);
        $this->Component->getController()->setRequest($postRequest);
        //</editor-fold>

        $result = $this->Component->setPrefs();
        $expected = ['prefs' => [
            'nested' => ['value' => 'variant-value'],
            'value' => 'variant-value',
        ]];
        $this->assertEquals($expected, $result->getEntity()->getVariants(),
            'An additional valid setPrefs request did not result in the
            expected entity variants');
    }

}
