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

class PreferencesComponentTest extends TestCase
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
            'prefsWrapper' => PrefsBase::class]);
        PersonFactory::make(1)
            ->withUser()
            ->persist();


//        $this->Component->getPrefs(1);
    }

    public function testFor()
    {
        $this->assertInstanceOf(PreferencesComponent::class, $this->Component);
    }

    public function testGetEntity()
    {
        $wrapper = $this->Component->getPrefs(1);
        $this->assertInstanceOf(PrefsBase::class, $wrapper);
        var_export($wrapper);
    }

//    public function testGetForm()
//    {
//
//    }


}
