<?php /** @noinspection ALL */

namespace Prefs\Test;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Prefs\Controller\Component\PreferencesComponent;
use Prefs\Exception\UnknownPreferenceKeyException;
use Prefs\Lib\PrefsBase;
use Prefs\Model\Entity\Preference;
use Prefs\Test\Factory\PrefsPersonFactory;

class PreferencesSystemTest extends TestCase
{

    /**
     * @var PreferencesComponent
     */
    public $Component;


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
        $this->assertEquals(null, $prefs->for('bad.path'),
            'Form::for() did not return null for unknown path');
    }
    //</editor-fold>
}
