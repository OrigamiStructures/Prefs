<?php

namespace Prefs\Test;

use App\Constants\PrefCon;
use App\Lib\Prefs;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Form\Form;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Prefs\Controller\Component\PreferencesComponent;
use Prefs\Lib\PrefsBase;
use Prefs\Test\PrefForm;

class PreferencesComponentTest extends TestCase
{

    public function setUp() : void
    {
        $request = $this->createMock(ServerRequest::class);
        $controller = new Controller($request);
        $registry = new ComponentRegistry($controller);
        $this->Component = new PreferencesComponent($registry, ['concretePrefsForm' => PrefForm::class]);

        $this->Component->getPrefs(1);
    }

    public function testFor()
    {
        $this->assertInstanceOf(PreferencesComponent::class, $this->Component);
    }

//    public function testGetEntity()
//    {
//
//    }
//
//    public function testGetForm()
//    {
//
//    }


}
