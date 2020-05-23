<?php

namespace Prefs\Test;

use App\Constants\PrefCon;
use App\Form\PreferencesForm;
use App\Lib\Prefs;
use App\Test\Factory\UserFactory;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Form\Form;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Prefs\Controller\Component\PreferencesComponent;
use Prefs\Lib\PrefsBase;
use Prefs\Model\Entity\Preference;
use Prefs\Test\PrefForm;
use App\Test\Factory\PersonFactory;

class PreferencesEntitTest extends TestCase
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
        PersonFactory::make(1)
            ->withUser()
            ->persist();
    }

    public function testEntityFor()
    {
        PersonFactory::make(1)
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
        PersonFactory::make(1)
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
        PersonFactory::make(1)
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
        PersonFactory::make(1)
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
