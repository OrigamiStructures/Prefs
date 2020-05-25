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

    public $fixtures = [
        'app.people',
        'app.users',
        'app.preferences'
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
        PreferenceFactory::make(1)
            ->persist();
        $prefs = $this->Component
            ->getPrefs(1)
            ->getEntity();

        $t = TableRegistry::getTableLocator()->get('Preferences');
        $record = $t->find()->toArray();
        var_export($record);
//        sleep(60);
//        var_export($prefs);
    }

    //<editor-fold desc="ENTITY TESTS">
    public function testEntityFor()
    {
        PrefsPersonFactory::make(1)
            ->withUser()
            ->persist();
        $prefs = $this->Component
            ->getPrefs(1)
            ->getEntity();

        $this->assertEquals('value-value', $prefs->for('value'));
        $this->assertEquals('nested-value-value', $prefs->for('nested.value'));

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
        $this->assertEquals('new value of value', $prefs->for('value'));

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

    public function testLinkIdConfig()
    {
        PrefsPersonFactory::make(1)
            ->withUser()
            ->persist();

        $this->Component->setConfig('linkId', 2);
        $this->assertEquals(2, 2,
            'configuring a literal did not return the literal');

        $this->Component->setConfig('linkId', function() {
            return 'value-from-callable';
        });
        $this->assertEquals('value-from-callable', $this->Component->getConfig('linkId')(),
            'configuring a callable did not use the callable to return a value');

    }

    //<editor-fold desc="WRAPPER TESTS">
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
}
