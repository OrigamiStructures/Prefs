<?php


namespace Prefs\Controller;

use Prefs\Controller\AppController;
use Prefs\Exception\BadPrefsImplementationException;
use Cake\ORM\Query;

class PreferencesController extends AppController
{

    public $components = ['Preferences'];

    /**
     * This will not be accessible for the API
     */
    public function setPrefs()
    {
        if (!$this->getRequest()->is(['post', 'patch', 'put']))
        {
            $msg = __("Preferences can only be changed through POST or PUT");
            throw new BadPrefsImplementationException($msg);
        }

        $this->Preferences->setPrefs();

        return $this->redirect($this->referer());
    }
}
