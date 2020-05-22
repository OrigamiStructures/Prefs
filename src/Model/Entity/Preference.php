<?php
namespace Prefs\Model\Entity;

use Prefs\Exception\BadPrefsEntityConfigurationException;
use Cake\ORM\Entity;
use Cake\Utility\Hash;
use Prefs\Exception\UnknownPreferenceKeyException;
use Cake\I18n\FrozenTime;

/**
 * Preference Entity
 *
 * To get a propery constructed entity you must use the getter
 * PreferencesComponent::getUsersPrefsEntity(user_id) or
 * (Concrete)PreferencesForm::getUsersPrefsEntity(user_id).
 * These methods will set the $this::defaults property
 *
 * @property int $id
 * @property FrozenTime|null $created
 * @property FrozenTime $modified
 * @property array $prefs
 * @property string $user_id
 *
 */
class Preference extends Entity
{

    /**
     * Default values for preferences
     *
     * Set by the PreferenceForm class using the current schema
     * [path.to.value => value]
     *
     * @var array
     */
    private $defaults = false;

    /**
     * Fields fields used for newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        'prefs' => true,
        'user_id' => true,
    ];

    /**
     * Get the current value for a preference
     *
     * Will use the user's value if present, otherwise, the default value
     *
     * @param $path
     * @param string $rootCol
     * @return mixed
     */
    public function for($path, $rootCol = 'prefs.')
    {
        $path = $rootCol.$path;

        $this->validateStructure();
        $this->validatePath($path);
        return Hash::get($this->prefs ?? [], $path) ?? $this->defaults[$path];
    }

    /**
     * Provide the array of defaults
     *
     * [path.to.value => value]
     *
     * @param $defaults
     * @return $this
     */
    public function setDefaults($defaults)
    {
        $this->defaults = $defaults;
        $this->clean();
        return $this;
    }

    /**
     * Returns all defaults
     *
     * [path.to.pref => value]
     *
     * @return array
     * @throws BadPrefsEntityConfigurationException
     */
    public function getDefaults()
    {
        $this->validateStructure();
        return $this->defaults;
    }

    /**
     * Get the array of user prefs that aren't defaults
     *
     * [path =>
     *      [to =>
     *          [pref => value]
     *      ]
     * ]
     *
     * @return array
     */
    public function getVariants()
    {
        return $this->prefs ?? [];
    }

    /**
     * Swap in a new prefs array
     *
     * [path =>
     *      [to =>
     *          [pref => value]
     *      ]
     * ]
     *
     * @param $array
     */
    public function setVariants($array) {
        $this->prefs = $array;
    }

    /**
     * Insert (or overwrite) a value in the user's preferences
     *
     * @param $path
     * @param $value
     * @param string $rootCol
     */
    public function setVariant($path, $value, $rootCol = 'prefs.')
    {
        $path = $rootCol.$path;

        $this->prefs = Hash::insert($this->prefs ?? [], $path, $value);
    }

    /**
     * Get a single user value or null if they haven't moved from default
     *
     * @param $path
     * @param string $rootCol
     * @return mixed
     */
    public function getVariant($path, $rootCol = 'prefs.')
    {
        $path = $rootCol.$path;

        return Hash::get($this->prefs ?? [], $path);
    }

    /**
     * get the user id
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    public function __debugInfo():array
    {
        $data = [
            'defaults' => $this->defaults
        ];
        $original = parent::__debugInfo();
        return array_merge($data, $original);
    }

    /**
     * Insure the entity was constructed properly
     *
     * Developer aid to guaranteed the plugin is being used properly
     * @throws BadPrefsEntityConfigurationException
     */
    private function validateStructure(): void
    {
        if ($this->defaults === false) {
            $msg = "Preferenes entity must have the default preference values set.";
            throw new BadPrefsEntityConfigurationException($msg);
        }
    }

    /**
     * Insure the path has been configured in the schema
     *
     * Developer aid to guarantee the plugin is configured properly
     *
     * @param $path
     * @throws UnknownPreferenceKeyException
     */
    private function validatePath($path)
    {
        if (Hash::check(array_keys($this->getDefaults()), $path)) {
            $msg = "The preference '$path' has not been defined in PreferencesTable::defaults yet.";
            throw new UnknownPreferenceKeyException($msg);
        }
    }
}
