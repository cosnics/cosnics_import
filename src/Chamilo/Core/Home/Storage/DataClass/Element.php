<?php
namespace Chamilo\Core\Home\Storage\DataClass;

use Chamilo\Libraries\Storage\DataClass\CompositeDataClass;
use Chamilo\Libraries\Storage\DataClass\Listeners\DisplayOrderDataClassListener;
use Chamilo\Libraries\Storage\DataClass\Listeners\DisplayOrderDataClassListenerSupport;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Chamilo\Libraries\Storage\Parameters\DataClassCountParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 *
 * @package Chamilo\Core\Home\Storage\DataClass
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
abstract class Element extends CompositeDataClass implements DisplayOrderDataClassListenerSupport
{
    const PROPERTY_CONFIGURATION = 'configuration';
    const PROPERTY_PARENT_ID = 'parent_id';
    const PROPERTY_SORT = 'sort';
    const PROPERTY_TITLE = 'title';
    const PROPERTY_TYPE = 'type';
    const PROPERTY_USER_ID = 'user_id';

    /**
     * @param string[] $default_properties
     *
     * @throws \Exception
     */
    public function __construct($default_properties = array())
    {
        parent::__construct($default_properties);
        $this->add_listener(new DisplayOrderDataClassListener($this));
    }

    public function delete()
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(Element::class, static::PROPERTY_PARENT_ID),
            new StaticConditionVariable($this->get_id())
        );
        $childElements = DataManager::retrieves(Block::class, new DataClassRetrievesParameters($condition));

        foreach ($childElements as $childElement)
        {
            if (!$childElement->delete())
            {
                return false;
            }
        }

        return parent::delete();
    }

    /**
     *
     * @return integer
     */
    public function getConfiguration()
    {
        return unserialize($this->get_default_property(self::PROPERTY_CONFIGURATION));
    }

    /**
     *
     * @param string[] $configurationVariables
     *
     * @return string[]
     */
    public static function getConfigurationVariables($configurationVariables = array())
    {
        return $configurationVariables;
    }

    /**
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->get_default_property(self::PROPERTY_PARENT_ID);
    }

    /**
     *
     * @param string $variable
     *
     * @return string
     */
    public function getSetting($variable, $defaultValue = null)
    {
        $configuration = $this->getConfiguration();

        return (isset($configuration[$variable]) ? $configuration[$variable] : $defaultValue);
    }

    /**
     *
     * @return integer
     */
    public function getSort()
    {
        return $this->get_default_property(self::PROPERTY_SORT);
    }

    /**
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->get_default_property(self::PROPERTY_TITLE);
    }

    /**
     *
     * @return string
     */
    public function getType()
    {
        return $this->get_default_property(self::PROPERTY_TYPE);
    }

    /**
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->get_default_property(self::PROPERTY_USER_ID);
    }

    public static function get_default_property_names($extended_property_names = array())
    {
        return parent::get_default_property_names(
            array(
                self::PROPERTY_TYPE,
                self::PROPERTY_PARENT_ID,
                self::PROPERTY_TITLE,
                self::PROPERTY_SORT,
                self::PROPERTY_USER_ID,
                self::PROPERTY_CONFIGURATION
            )
        );
    }

    public function get_display_order_context_properties()
    {
        return array(new PropertyConditionVariable(Element::class, self::PROPERTY_PARENT_ID));
    }

    public function get_display_order_property()
    {
        return new PropertyConditionVariable(Element::class, self::PROPERTY_SORT);
    }

    /**
     * @return string
     */
    public static function get_table_name()
    {
        return 'home_element';
    }

    /**
     *
     * @return string
     * @deprecated User Element::getType() now
     */
    public function get_type()
    {
        return $this->getType();
    }

    public function hasChildren()
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(Element::class, self::PROPERTY_PARENT_ID),
            new StaticConditionVariable($this->get_id())
        );

        $childCount = DataManager::count(Block::class, new DataClassCountParameters($condition));

        return ($childCount == 0);
    }

    public function isOnTopLevel()
    {
        return $this->getParentId() == 0;
    }

    /**
     *
     * @param string $variable
     */
    public function removeSetting($variable)
    {
        $configuration = $this->getConfiguration();
        unset($configuration[$variable]);

        $this->setConfiguration($configuration);
    }

    /**
     *
     * @param integer $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->set_default_property(self::PROPERTY_CONFIGURATION, serialize($configuration));
    }

    /**
     *
     * @param integer $parentId
     */
    public function setParentId($parentId)
    {
        $this->set_default_property(self::PROPERTY_PARENT_ID, $parentId);
    }

    /**
     *
     * @param string $variable
     * @param string $value
     */
    public function setSetting($variable, $value)
    {
        $configuration = $this->getConfiguration();
        $configuration[$variable] = $value;

        $this->setConfiguration($configuration);
    }

    /**
     *
     * @param integer $sort
     */
    public function setSort($sort)
    {
        $this->set_default_property(self::PROPERTY_SORT, $sort);
    }

    /**
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->set_default_property(self::PROPERTY_TITLE, $title);
    }

    /**
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->set_default_property(self::PROPERTY_TYPE, $type);
    }

    /**
     *
     * @param integer $userId
     */
    public function setUserId($userId)
    {
        $this->set_default_property(self::PROPERTY_USER_ID, $userId);
    }

    /**
     *
     * @param string $type
     *
     * @throws \Exception
     * @deprecated Use Element::setType() now
     */
    public function set_type($type)
    {
        $this->setType($type);
    }
}