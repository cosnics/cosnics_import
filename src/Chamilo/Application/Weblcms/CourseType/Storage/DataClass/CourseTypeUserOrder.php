<?php
namespace Chamilo\Application\Weblcms\CourseType\Storage\DataClass;

use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Storage\DataClass\Listeners\DisplayOrderDataClassListener;
use Chamilo\Libraries\Storage\DataClass\Listeners\DisplayOrderDataClassListenerSupport;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;

/**
 * Describes a course type user order (personal ordering of the course types per user)
 *
 * @package application\weblcms\course_type;
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class CourseTypeUserOrder extends DataClass implements DisplayOrderDataClassListenerSupport
{
    const PROPERTY_COURSE_TYPE_ID = 'course_type_id';
    const PROPERTY_DISPLAY_ORDER = 'display_order';
    const PROPERTY_USER_ID = 'user_id';

    /**
     * Constructor
     *
     * @param mixed[string] $default_properties
     * @param mixed[string] $optional_properties
     */
    public function __construct($default_properties = [], $optional_properties = [])
    {
        parent::__construct($default_properties, $optional_properties);
        $this->addListener(new DisplayOrderDataClassListener($this));
    }

    /**
     * Changes the current display order added with the given value
     *
     * @param $count int
     */
    public function change_display_order_with_count($count)
    {
        $this->set_display_order($this->get_display_order() + $count);
    }

    /**
     * Returns the course_type_id of this CourseType object
     *
     * @return String
     */
    public function get_course_type_id()
    {
        return $this->getDefaultProperty(self::PROPERTY_COURSE_TYPE_ID);
    }

    /**
     * **************************************************************************************************************
     * Helper Functionality *
     * **************************************************************************************************************
     */

    /**
     * Returns the default properties of this dataclass
     *
     * @param string[] $extendedPropertyNames
     *
     * @return string[]
     */
    public static function getDefaultPropertyNames(array $extendedPropertyNames = []): array
    {
        return parent::getDefaultPropertyNames(
            array(self::PROPERTY_COURSE_TYPE_ID, self::PROPERTY_USER_ID, self::PROPERTY_DISPLAY_ORDER)
        );
    }

    /**
     * **************************************************************************************************************
     * Getters and Setters *
     * **************************************************************************************************************
     */

    /**
     * Returns the display_order of this CourseType object
     *
     * @return int
     */
    public function get_display_order()
    {
        return $this->getDefaultProperty(self::PROPERTY_DISPLAY_ORDER);
    }

    /**
     * @return \Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable[]
     */
    public function getDisplayOrderContextProperties(): array
    {
        return array(new PropertyConditionVariable(self::class, self::PROPERTY_USER_ID));
    }

    public function getDisplayOrderProperty(): PropertyConditionVariable
    {
        return new PropertyConditionVariable(self::class, self::PROPERTY_DISPLAY_ORDER);
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'weblcms_course_type_user_order';
    }

    /**
     * Returns the user_id of this CourseType object
     *
     * @return String
     */
    public function get_user_id()
    {
        return $this->getDefaultProperty(self::PROPERTY_USER_ID);
    }

    /**
     * Sets the course_type_id of this CourseType object
     *
     * @param $course_type_id String
     */
    public function set_course_type_id($course_type_id)
    {
        $this->setDefaultProperty(self::PROPERTY_COURSE_TYPE_ID, $course_type_id);
    }

    /**
     * **************************************************************************************************************
     * Display Order Functionality *
     * **************************************************************************************************************
     */

    /**
     * Sets the display_order of this CourseType object
     *
     * @param $display_order int
     */
    public function set_display_order($display_order)
    {
        $this->setDefaultProperty(self::PROPERTY_DISPLAY_ORDER, $display_order);
    }

    /**
     * Sets the user_id of this CourseType object
     *
     * @param $user_id String
     */
    public function set_user_id($user_id)
    {
        $this->setDefaultProperty(self::PROPERTY_USER_ID, $user_id);
    }
}
