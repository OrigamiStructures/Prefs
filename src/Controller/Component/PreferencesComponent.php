<?php
namespace Prefs\Controller\Component;

use Cake\ORM\Table;
use Prefs\Form\PreferencesForm;
use Prefs\Lib\PrefsBase;
use Prefs\Model\Entity\Preference;
use Prefs\Model\Table\PreferencesTable;
use Cake\Controller\Component;
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
     * @var array Components used by this component
     */
    public $components = ['Flash'];

    /**
     * @var PreferencesForm
     */
    protected $Form = false;

    /**
     * Prefs object
     *s
     * @var array
     */
    protected $registry = false;

    protected $Prefs = [];

    /**
     * @var bool|string|int|callable
     */
    protected $LinkId = false;

    public $_defaultConfig = [
        'linkId' => false,
    ];


    /**
     * Using this component will automatically make PreferencesHelper available
     *
     * @param array $config
     */
    public function initialize(array $config):void
    {
        parent::initialize($config);
        $this->getController()->viewBuilder()->addHelpers(['Prefs.Preferences']);
        $this->setConfig('concretePrefsForm', $config['concretePrefsForm']);
        if (isset($config['linkId'])) {
            $this->setConfig('linkId', $config['linkId']);
        }
    }

    /**
     * Discover the id of the user-owner of the prefs
     *
     * This is a user_id used to retrieve a Preference record
     *
     * Assumed to be AuthMiddleware Identity but config allows
     * injection of a value or callable alternative
     *
     * @return string
     */
    protected function getLinkId()
    {
        if(!$this->getConfig('linkId')) {
            return $this->getController()->getIdentity()->getIdentifier();
        }
        elseif (is_callable($this->getConfig('linkId'))) {
            return $this->getConfig('linkId')();
        }
        else {
            return $this->getConfig('linkId');
        }
    }

    /**
     * Process a request to update persisted preference data
     *
     * Uses the Form object and its schema to validate the data.
     * Any values that match schema defaults will not be persisted.
     * Flash messages are prepared to let the user know how the
     * request went and what was saved.
     *
     * @return Preference
     * @throws BadMethodCallException
     */
    public function setPrefs()
    {
        $post = $this->getController()->getRequest()->getData();
        $form = $this->getFormObjet();
        $entity = $this->repository()->getPreferencesFor($this->getLinkId());

        if ($form->validate($post)) {
            $userVariants = $entity->getVariants();
            $prefsDefaults = $this->getPrefsDefaults();

            $allowedPrefs = collection($form->getValidPaths());
            $newVariants = $allowedPrefs
                ->reduce(function($accum, $path) use ($post, $prefsDefaults, $userVariants){
                    //if the post is default, leave variant out of the list
                    //if post is non-default, non-null
                    // or variant is non-null, variant must be included
                    // and we prefer post if its different than variant and not null
                    $postValue = Hash::get($post, $path);
                    $variantValue = Hash::get($userVariants, $path);
                    if (
                        $postValue != $prefsDefaults[$path]
                        && (!is_null($variantValue) || !is_null($postValue))) {
                        $accum = Hash::insert(
                            $accum,
                            $path,
                            $variantValue != $postValue ? $postValue ?? $variantValue : $variantValue
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
     * @noinspection PhpUnused
     */
    public function clearPrefs()
    {
        //read the persisted prefs
        $repository = $this->repository();
        $prefs = $repository->getPreferencesFor($this->getLinkId());
        /* @var Preference $prefs */

        $prefs = $repository->patchEntity($prefs, ['prefs' => []]);

        if ($repository->save($prefs)) {
            $this->Flash->success('Your preferences were reset to the default values.');
        } else {
            $this->Flash->error('Your preferences were no reset. Please try again');
        }
        return;
    }

    /**
     * Get the ModellessForm object to use as a Form::create context
     *
     * The object will carry all user settings to the form as values
     *
     * @param $user_id
     * @param array $variants
     * @return PreferencesForm
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
     * Fully stocked with all the default settings and user variants.
     * This also insures that the entity will not include variants that
     * are now defined as 'defaults' in the schema. Nor will they contain
     * path=>value pairs for anything paths that have been deleted
     * from the schema.
     *
     * @return Preference
     */
    protected function getUserPrefsEntity()
    {
        /* @var Preference $UserPrefs */
        /* @var PreferencesForm $Form */
        /* @var PreferencesTable $PrefsTable */

        $UserPrefs = TableRegistry::getTableLocator()->get('Prefs.Preferences')
            ->getPreferencesFor($this->getLinkId());

        $defaults = $this->getFormObjet()->getDefaults();
        $stored_variants = Hash::flatten($UserPrefs->getVariants());

        //set the default values into the entity
        $UserPrefs->setDefaults($defaults);

        //checked store variants against currently defined schema
        $current_variants = collection($stored_variants)
            ->reduce(function($accum, $variant, $key) use ($defaults) {
                if (
                    array_key_exists($key, $defaults)
                    && (string) $variant != (string) $defaults[$key]
                ) {
                    $accum[$key] = $variant;
                }
                return $accum;
            }, []);

        //if the variant list changed during filtering, save the corrected version
        if ($current_variants !== $stored_variants) {
            $UserPrefs->setVariants(Hash::expand($current_variants));
            $UserPrefs->setDirty('prefs', true);
            $PrefsTable = TableRegistry::getTableLocator()->get('Prefs.Preferences');
            $PrefsTable->save($UserPrefs);
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
     * @noinspection PhpFullyQualifiedNameUsageInspection
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
     * @return PreferencesForm
     */
    protected function getFormObjet()
    {
        if ($this->Form !== false) {
            //Return already established class
            return $this->Form;
        }
        else {
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
     * @return Table
     */
    private function repository()
    {
        return TableRegistry::getTableLocator()->get('Prefs.Preferences');
    }

    /**
     * Saves prefs changes and emits Flash messages describing the result
     *
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
     *
     * @return PrefsBase
     * @throws BadMethodCallException
     */
    public function getPrefs() : PrefsBase
    {

        if (!$this->registry) {
            $entity = $this->getUserPrefsEntity();

            /* @var PrefsBase $prefsWrapper */
            $prefsWrapper = $this->getConfig('prefsWrapper');

            $this->registry = new $prefsWrapper(
                $entity,
                $this->getFormContextObject($this->getLinkId(), $entity->getVariants())
            );
        }
        return $this->registry;
    }
}
