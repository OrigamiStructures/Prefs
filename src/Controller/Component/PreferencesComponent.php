<?php
namespace Prefs\Controller\Component;

use Prefs\Controller\AppController;
use Prefs\Form\PreferencesForm;
use Prefs\Lib\PrefsBase;
use Prefs\Model\Entity\Preference;
use Prefs\Model\Table\PreferencesTable;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\App;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use BadMethodCallException;
use Cake\Controller\Component\FlashComponent;

/**
 * Class PreferencesComponent
 * @package App\Controller\Component
 *
 * @property FlashComponent $Flash
 */
class PreferencesComponent extends Component
{

    /**
     * A reference to the Preferences table object
     *
     * @var bool|PreferencesTable
     */
    private $repository = false;

    /**
     * @var array Components used by this component
     */
    public $components = ['Flash'];

    /**
     * A user override class for the Form if provided
     *
     * @var bool|string
     */
    private $formClass = false;

    protected $Form = false;

    /**
     * Prefs objects that have been created index by the id that made them
     *
     * @var array
     */
    protected $registry = [];

    protected $Prefs = [];

    /**
     * Using this component will automatically make PreferencesHelper available
     *
     * @param array $config
     */
    public function initialize(array $config):void
    {
        parent::initialize($config);
        $this->getController()->viewBuilder()->setHelpers(['Prefs.Preferences']);
        $this->setConfig('concretePrefsForm', $config['concretePrefsForm']);
    }

    /**
     *
     * @return Preference
     * @throws BadMethodCallException
     */
    public function setPrefs()
    {
        $post = $this->getController()->getRequest()->getData();
        $form = $this->getFormObjet();
        $entity = $this->repository()->getPreferencesFor($post['id']);

        if ($form->validate($post)) {
            $userVariants = $entity->getVariants();
            $prefsDefaults = $this->getPrefsDefaults();

            $allowedPrefs = collection($form->getValidPaths());
            $newVariants = $allowedPrefs
                ->reduce(function($accum, $path) use ($post, $prefsDefaults, $userVariants){
                    //if the post is default, leave variant out of the list
                    //if post is non-default, non-null
                    // or variant is non-null, variant must be included
                    // and we prefer post if its different than variant
                    $postValue = Hash::get($post, $path);
                    $variantValue = Hash::get($userVariants, $path);
                    if ( $postValue == $prefsDefaults[$path]) {
                        //let variant evaporate
                    } elseif (!is_null($variantValue) ||  !is_null($postValue)) {
                        $accum = Hash::insert(
                            $accum,
                            $path,
                            $variantValue != $postValue ? $postValue : $variantValue
                        );
                    }
                    return $accum;
                }, []);

            if ($newVariants != $userVariants) {
                $entity->setVariants($newVariants);
                $this->savePrefs($post, $entity);
            } else {
                $this->Flash->success('No new preferences were requested');
            }
         } else {
            //didn't validate
            $form->errorsToFlash($this->Flash);
        }

        /* @var PrefsBase $prefsWrapper */
        $prefsWrapper = $this->getConfig('prefsWrapper');

        return new $prefsWrapper($entity, $form);
}

    /**
     * Unset one user preference
     *
     * @param $path
     */
    public function clearPrefs($user_id)
    {
        //read the persisted prefs
        $prefs = $this->repository()->getPreferencesFor($user_id);
        /* @var Preference $prefs */

        $prefs = $this->repository()->patchEntity($prefs, ['prefs' => []]);

        if ($this->repository()->save($prefs)) {
            $this->Flash->success('Your preferences were reset to the default values.');
        } else {
            $this->Flash->error('Your preferences were no reset. Please try again');
        }
        return;
    }

    /**
     * Fully namespaced name of an override PreferencesForm class
     *
     * Normally LocalPreferencesForm extends PreferencesForm is used.
     * But any MyPrefForm extends PreferencesForm can be substituted
     *
     * @param $formClass
     */
    public function setFormClass($formClass)
    {
        $this->formClass = $formClass;
    }

    /**
     * Get the ModellessForm object to use as a Form::create context
     *
     * The object will carry all user settings to the form as values
     *
     * @param $user_id
     * @return LocalPreferencesForm
     */
    protected function getFormContextObject($user_id = null, $variants = [])
    {
        if (is_null($user_id)) {
            return $this->getFormObjet();
        }
        return $this->getFormObjet()->asContext($user_id, $variants);
    }

    /**
     * Get the user's preference entity
     *
     * fully stocked with all the default settings and user variants
     *
     * @todo test that changing the defaults in the implementation
     *      clears the corresponding value in the entity when the
     *      values overlap
     *
     * @param $user_id
     * @return Preference
     */
    protected function getUserPrefsEntity($user_id)
    {

//        return $this->getFormObjet()->getUsersPrefsEntity($user_id);

        /* @var  Preference $userPrefs */
        /* @var PreferencesForm $Form */
        /* @var PreferencesTable $PrefsTable */

        /* @todo property is never useed */
        /* @todo $user_id is never null */
        $this->user_id = $user_id;
        if (is_null($user_id)) {
            $UserPrefs = new Preference([]);
        } else {
            $UserPrefs = TableRegistry::getTableLocator()->get('Prefs.Preferences')
                ->getPreferencesFor($user_id);
        }

        /* @todo is there a lower impact way of getting schema than loading a Form object? */
        $schema = $this->getFormObjet()->schema();
        $defaults = [];
        $prefs = [];

        //Make a list of all default values
        //And filter any invalid prefs out of the json object
        foreach ($schema->fields() as $path) {
            $defaultValue = $schema->field($path)['default'];
            $defaults[$path] = $defaultValue;
            if (!in_array($UserPrefs->getVariant($path), [null, $defaultValue])) {
                $prefs = Hash::insert($prefs, $path, $UserPrefs->getVariant($path));
            }
        }
        //set the default values into the entity
        $UserPrefs->setDefaults($defaults);

        //if the prefs list changed during filtering, save the corrected version
        if ($UserPrefs->getVariants() != $prefs) {
            $UserPrefs->setVariants($prefs);
            $PrefsTable = TableRegistry::getTableLocator()->get('Preferences');
            $PrefsTable
                ->save($PrefsTable->patchEntity(
                    $UserPrefs,
                    $prefs == []
                        ? [
                            'prefs' => [],
                            'id' => $UserPrefs->id
                          ]
                        : $prefs));
        }
        return $UserPrefs;
    }

    /**
     * Get the [path => value] array of all prefs and their default values
     * @return array
     */
    protected function getPrefsDefaults()
    {
        return $this->getFormObjet()->getDefaults();
    }

    /**
     * Get the array of non-default settings for the user
     *
     * @param $user_id
     * @return array
     */
    protected function getUserVariants($user_id)
    {
        return $this->getFormObjet()->getUsersPrefsEntity($user_id)->getVariants();
    }

    /**
     * Make a simple object with versions of the posted user prefs for messaging
     *
     * $post is posted data array
     *  [
     *      ['path.to.set' => 'value']
     *      ['another.pref => '42']
     *  ]
     *
     * From that example $prefsSummary will be:
     * stdClass {
     *  post =              ['path.to.set' => 'value',
     *                       'another.pref' => '42']
     *  summaryArray =      ['path, to, set = value',
     *                       'another, pref = 42']
     *  summaryStatement =  'path, to, set = value and another, path = 42'
     *  count =             2
     * }
     *
     * @param array $post
     * @return \stdClass
     */
    private function summarizeSettings(array $post): \stdClass
    {
        $validPaths = $this->getFormObjet()->getValidPaths();
        $settings = collection(Hash::flatten($post));
        $settingSummaries = $settings->reduce(function ($accum, $value, $path) use ($validPaths) {
            if (in_array($path, $validPaths)) {
                $pref = str_replace('.', ', ', $path);
                $accum[] = "[$pref = $value]";
            }
            return $accum;
        }, []);
        $prefsSummary = new \stdClass();
        $prefsSummary->post = $post;
        $prefsSummary->summaryArray = $settingSummaries;
        $prefsSummary->summaryStatement = Text::toList($settingSummaries);
        $prefsSummary->count = count($settingSummaries);

        return $prefsSummary;
    }

    /**
     * This object knows the schema but nothing about the users settings
     *
     * @return PreferencesForm|LocalPreferencesForm
     */
    protected function getFormObjet()
    {
        if ($this->Form !== false) {
            //Return already established class
            return $this->Form;
        }
        if (!$this->formClass == false) {
            //Create new instance of already established classname
            $class = $this->formClass;
            $PreferenceForm = new $class();
        } else {
            //Create new class from configuration
            /* @var PreferencesForm $concretePrefsForm*/
            $concretePrefsForm = $this->getConfig('concretePrefsForm');

            $PreferenceForm = new $concretePrefsForm();
        }
        $this->Form = $PreferenceForm;
        return $PreferenceForm;
    }

    /**
     * Get the Preferences table instance
     *
     * @return PreferencesTable
     */
    private function repository()
    {
        if ($this->repository === false) {
            $this->repository = TableRegistry::getTableLocator()->get('Prefs.Preferences');
        }
        return $this->repository;
    }

    /**
     * @param $post
     * @param Preference $prefs
     */
    protected function savePrefs($post, Preference $prefs): void
    {
        $settingSummaries = $this->summarizeSettings($post ?? []);

        if ($this->repository()->save($prefs)) {
            $msg = $settingSummaries->count > 1
                ? __("Your preferences $settingSummaries->summaryStatement were saved.")
                : __("Your preference for $settingSummaries->summaryStatement was saved.");
            $this->Flash->success($msg);
        } else {
            $msg = $settingSummaries->count > 1
                ? __("Your preferences $settingSummaries->summaryStatement were not saved. Please try again")
                : __("Your preference for $settingSummaries->summaryStatement was not saved. Please try again");
            $this->Flash->error($msg);
        }
    }

    /**
     * Returns the full Prefs object for use in any situation
     *
     * Contains an Entity to describe the current settings
     *
     * Contains a Form to describe the full preference schema
     * and to act as a context object in FormHelper::create().
     *
     * Can emit either of those objects.
     *
     * Provides access to all prefs-related constants
     *
     * @todo develope class iterface
     * @todo clarify returning object as the concrete instantiation of prefs base
     *
     * @param $user_id null|string null will get full default objects
     */
    public function getPrefs($user_id = null) : PrefsBase
    {

        /*
         * @todo kill registry. use property and separate, aliased components
         */
        if (!isset($this->registry[$user_id])) {
            $entity = $this->getUserPrefsEntity($user_id);

            /* @var PrefsBase $prefsWrapper */
            $prefsWrapper = $this->getConfig('prefsWrapper');

            $this->registry[$user_id] = new $prefsWrapper(
                $entity,
                $this->getFormContextObject($user_id, $entity->getVariants())
            );
        }
        return $this->registry[$user_id];
    }
}
