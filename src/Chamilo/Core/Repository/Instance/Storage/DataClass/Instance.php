<?php
namespace Chamilo\Core\Repository\Instance\Storage\DataClass;

use Chamilo\Core\Repository\Instance\Rights;
use Chamilo\Core\Repository\Instance\Storage\DataManager;
use Chamilo\Libraries\Storage\DataClass\CompositeDataClass;
use Chamilo\Libraries\Storage\Parameters\DataClassCountParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrieveParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 *
 * @package core\repository\instance
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class Instance extends CompositeDataClass
{
    const PROPERTY_CREATED = 'created';
    const PROPERTY_ENABLED = 'enabled';
    const PROPERTY_IMPLEMENTATION = 'implementation';
    const PROPERTY_MODIFIED = 'modified';
    const PROPERTY_TITLE = 'title';

    /**
     * Contains a list of already required export types Allow to spare some business logic processing
     *
     * @var array
     */
    private static $already_required_types = [];

    public function activate()
    {
        $this->set_enabled(true);
    }

    /**
     * *********************************************************************** Fat model methods
     * ***********************************************************************
     */
    public function create()
    {
        if (!parent::create())
        {
            return false;
        }
        else
        {
            if (!Setting::initialize($this))
            {
                return false;
            }
        }

        $succes = Rights::getInstance()->create_location_in_external_instances_subtree(
            $this->get_id(), Rights::getInstance()->get_external_instances_subtree_root_id()
        );
        if (!$succes)
        {
            return false;
        }

        return true;
    }

    public function deactivate()
    {
        $this->set_enabled(false);
    }

    public function delete()
    {
        if (!parent::delete())
        {
            return false;
        }
        else
        {
            $condition = new EqualityCondition(
                new PropertyConditionVariable(Setting::class, Setting::PROPERTY_EXTERNAL_ID),
                new StaticConditionVariable($this->get_id())
            );
            $settings = DataManager::retrieves(Setting::class, new DataClassRetrievesParameters($condition));

            foreach ($settings as $setting)
            {
                if (!$setting->delete())
                {
                    return false;
                }
            }
        }

        $location = Rights::getInstance()->get_location_by_identifier_from_external_instances_subtree($this->get_id());
        if ($location)
        {
            if (!$location->delete())
            {
                return false;
            }
        }

        return true;
    }

    public function get_creation_date()
    {
        return $this->get_default_property(self::PROPERTY_CREATED);
    }

    public static function get_default_property_names($extended_property_names = [])
    {
        $extended_property_names[] = self::PROPERTY_TITLE;
        $extended_property_names[] = self::PROPERTY_IMPLEMENTATION;
        $extended_property_names[] = self::PROPERTY_ENABLED;
        $extended_property_names[] = self::PROPERTY_CREATED;
        $extended_property_names[] = self::PROPERTY_MODIFIED;

        return parent::get_default_property_names($extended_property_names);
    }

    /**
     *
     * @return boolean Indicates wether the export is enabled or not
     */
    public function get_enabled()
    {
        return $this->get_default_property(self::PROPERTY_ENABLED, false);
    }

    /**
     *
     * @return string The implementation
     */
    public function get_implementation()
    {
        return $this->get_default_property(self::PROPERTY_IMPLEMENTATION);
    }

    public function get_modification_date()
    {
        return $this->get_default_property(self::PROPERTY_MODIFIED);
    }

    public function get_setting($variable)
    {
        return DataManager::retrieve_setting_from_variable_name($variable, $this->get_id());
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'repository_instance_instance';
    }

    /**
     *
     * @return string The export title
     */
    public function get_title()
    {
        return $this->get_default_property(self::PROPERTY_TITLE);
    }

    public function get_user_setting($user_id, $variable)
    {
        $conditions = [];
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(Setting::class, Setting::PROPERTY_VARIABLE),
            new StaticConditionVariable($variable)
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(Setting::class, Setting::PROPERTY_USER_ID),
            new StaticConditionVariable($user_id)
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(Setting::class, Setting::PROPERTY_EXTERNAL_ID),
            new StaticConditionVariable($this->get_id())
        );
        $condition = new AndCondition($conditions);

        return DataManager::retrieve(Setting::class, new DataClassRetrieveParameters($condition));
    }

    public function has_settings()
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(Setting::class, Setting::PROPERTY_EXTERNAL_ID),
            new StaticConditionVariable($this->get_id())
        );

        $settings = DataManager::count(Setting::class, new DataClassCountParameters($condition));

        return $settings > 0;
    }

    public function is_enabled()
    {
        return $this->get_enabled();
    }

    public function set_creation_date($created)
    {
        if (isset($created))
        {
            $this->set_default_property(self::PROPERTY_CREATED, $created);
        }
    }

    public function set_enabled($enabled)
    {
        if (isset($enabled) && is_bool($enabled))
        {
            $this->set_default_property(self::PROPERTY_ENABLED, $enabled);
        }
    }

    public function set_implementation($implementation)
    {
        if (isset($implementation) && strlen($implementation) > 0)
        {
            $this->set_default_property(self::PROPERTY_IMPLEMENTATION, $implementation);
        }
    }

    public function set_modification_date($modified)
    {
        if (isset($modified))
        {
            $this->set_default_property(self::PROPERTY_MODIFIED, $modified);
        }
    }

    public function set_title($title)
    {
        if (isset($title) && strlen($title) > 0)
        {
            $this->set_default_property(self::PROPERTY_TITLE, $title);
        }
    }
}
