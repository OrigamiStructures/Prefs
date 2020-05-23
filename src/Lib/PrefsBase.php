<?php


namespace Prefs\Lib;


use Prefs\Exception\BadPrefsEntityConfigurationException;
use Prefs\Exception\UnknownPreferenceKeyException;
use Prefs\Form\PreferencesForm;
use Prefs\Model\Entity\Preference;
use Cake\Utility\Hash;

class PrefsBase
{
    /**
     * @var Preference
     */
    protected $entity;

    /**
     * @var PreferencesForm
     */
    protected $form;

    public  $lists = [];

    public function __construct(Preference $entity, PreferencesForm $form)
    {
        $this->entity = $entity;
        $this->form = $form;
        $this->form->Prefs = $this;
        return $this;
    }

    /**
     * Wrapper to Entity::for()
     *
     * @param $path
     * @return mixed
     * @throws BadPrefsEntityConfigurationException
     * @throws UnknownPreferenceKeyException
     */
    public function for($path)
    {
        return $this->getEntity()->for($path);
    }

    /**
     * @return Preference
     */
    public function getEntity(): Preference
    {
        return $this->entity;
    }

    /**
     * @return PreferencesForm
     */
    public function getForm(): PreferencesForm
    {
        return $this->form;
    }

}
