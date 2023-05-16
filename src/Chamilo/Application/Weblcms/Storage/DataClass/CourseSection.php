<?php
namespace Chamilo\Application\Weblcms\Storage\DataClass;

use Chamilo\Application\Weblcms\Manager;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Storage\DataClass\Listeners\DisplayOrderDataClassListener;
use Chamilo\Libraries\Storage\DataClass\Listeners\DisplayOrderDataClassListenerSupport;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Translation\Translation;
use Exception;

/**
 * This class defines a course section in which tools can be arranged
 *
 * @package application\weblcms;
 * @author  Sven Vanpoucke - Hogeschool Gent
 */
class CourseSection extends DataClass implements DisplayOrderDataClassListenerSupport
{
    public const CONTEXT = Manager::CONTEXT;

    public const PROPERTY_COURSE_ID = 'course_id';
    public const PROPERTY_DISPLAY_ORDER = 'display_order';
    public const PROPERTY_NAME = 'name';
    public const PROPERTY_TYPE = 'type';
    public const PROPERTY_VISIBLE = 'visible';

    public const TYPE_ADMIN = 3;
    public const TYPE_ADMIN_NAME = 'admin';
    public const TYPE_CUSTOM = 4;
    public const TYPE_CUSTOM_NAME = 'custom';
    public const TYPE_DISABLED = '0';
    public const TYPE_DISABLED_NAME = 'disabled';
    public const TYPE_LINK = 2;
    public const TYPE_LINK_NAME = 'link';
    public const TYPE_TOOL = 1;
    public const TYPE_TOOL_NAME = 'tool';

    private static $type_name_mapping = [
        self::TYPE_DISABLED => self::TYPE_DISABLED_NAME,
        self::TYPE_TOOL => self::TYPE_TOOL_NAME,
        self::TYPE_LINK => self::TYPE_LINK_NAME,
        self::TYPE_ADMIN => self::TYPE_ADMIN_NAME,
        self::TYPE_CUSTOM => self::TYPE_CUSTOM_NAME
    ];

    private $displayName;

    /**
     * @param string[] $default_properties
     * @param string[] $optional_properties
     */
    public function __construct($default_properties = [], $optional_properties = [])
    {
        parent::__construct($default_properties = $optional_properties);
        $this->addListener(new DisplayOrderDataClassListener($this));
    }

    /**
     * **************************************************************************************************************
     * Type Mapping Functionality *
     * **************************************************************************************************************
     */

    /**
     * Returns the default properties of this dataclass
     *
     * @return String[] - The property names.
     */
    public static function getDefaultPropertyNames(array $extendedPropertyNames = []): array
    {
        return parent::getDefaultPropertyNames(
            [
                self::PROPERTY_COURSE_ID,
                self::PROPERTY_NAME,
                self::PROPERTY_TYPE,
                self::PROPERTY_VISIBLE,
                self::PROPERTY_DISPLAY_ORDER
            ]
        );
    }

    protected function getDependencies(array $dependencies = []): array
    {
        $id = $this->get_id();

        return [
            CourseToolRelCourseSection::class => new EqualityCondition(
                new PropertyConditionVariable(
                    CourseToolRelCourseSection::class, CourseToolRelCourseSection::PROPERTY_SECTION_ID
                ), new StaticConditionVariable($id)
            )
        ];
    }

    /**
     * **************************************************************************************************************
     * Inherited Functionality *
     * **************************************************************************************************************
     */

    /**
     * @return string
     */
    public function getDisplayName()
    {
        if (!isset($this->displayName))
        {
            if ($this->getType() == CourseSection::TYPE_CUSTOM)
            {
                $this->displayName = $this->get_name();
            }
            else
            {
                $this->displayName = Translation::get($this->get_name());
            }
        }

        return $this->displayName;
    }

    /**
     * @return \Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable[]
     */
    public function getDisplayOrderContextProperties(): array
    {
        return [new PropertyConditionVariable(self::class, self::PROPERTY_COURSE_ID)];
    }

    /**
     * **************************************************************************************************************
     * Getters and Setters *
     * **************************************************************************************************************
     */

    public function getDisplayOrderProperty(): PropertyConditionVariable
    {
        return new PropertyConditionVariable(self::class, self::PROPERTY_DISPLAY_ORDER);
    }

    /**
     * @return string
     */
    public static function getStorageUnitName(): string
    {
        return 'weblcms_course_section';
    }

    /**
     * Returns the type property of this object
     *
     * @return int
     */
    public function getType()
    {
        return $this->getDefaultProperty(self::PROPERTY_TYPE);
    }

    /**
     * Returns the course_id property of this object
     *
     * @return String
     */
    public function get_course_id()
    {
        return $this->getDefaultProperty(self::PROPERTY_COURSE_ID);
    }

    /**
     * Returns the display_order property of this object
     *
     * @return int
     */
    public function get_display_order()
    {
        return $this->getDefaultProperty(self::PROPERTY_DISPLAY_ORDER);
    }

    /**
     * Returns the name property of this object
     *
     * @return String
     */
    public function get_name()
    {
        return $this->getDefaultProperty(self::PROPERTY_NAME);
    }

    /**
     * @deprecated Use CourseSection::getType() now
     */
    public function get_type()
    {
        return $this->getType();
    }

    /**
     * Returns the type from the given type name
     *
     * @param $type_name String
     *
     * @return int
     */
    public static function get_type_from_type_name($type_name)
    {
        $type = array_search($type_name, self::$type_name_mapping);

        if (!$type)
        {
            throw new Exception(Translation::get('CouldNotFindSectionTypeName', ['TYPE_NAME' => $type_name]));
        }

        return $type;
    }

    /**
     * Returns the type name from a given type
     *
     * @param $type int
     *
     * @return String
     */
    public static function get_type_name_from_type($type)
    {
        if (!array_key_exists($type, self::$type_name_mapping))
        {
            throw new Exception(Translation::get('CouldNotFindSectionType', ['TYPE' => $type]));
        }

        return self::$type_name_mapping[$type];
    }

    /**
     * Returns the tool_id property of this object
     *
     * @return bool
     */
    public function is_visible()
    {
        return $this->getDefaultProperty(self::PROPERTY_VISIBLE);
    }

    public function setType($type)
    {
        $this->setDefaultProperty(self::PROPERTY_TYPE, $type);
    }

    /**
     * Sets the course_id property of this object
     *
     * @param $course_id String
     */
    public function set_course_id($course_id)
    {
        $this->setDefaultProperty(self::PROPERTY_COURSE_ID, $course_id);
    }

    /**
     * Sets the display_order property of this object
     *
     * @param $display_order int
     */
    public function set_display_order($display_order)
    {
        $this->setDefaultProperty(self::PROPERTY_DISPLAY_ORDER, $display_order);
    }

    /**
     * Sets the name property of this object
     *
     * @param $name String
     */
    public function set_name($name)
    {
        $this->setDefaultProperty(self::PROPERTY_NAME, $name);
    }

    /**
     * @deprecated Use CourseSection::setType() now
     */
    public function set_type($type)
    {
        $this->setType($type);
    }

    /**
     * Sets the visible property of this object
     *
     * @param $visible bool
     */
    public function set_visible($visible)
    {
        $this->setDefaultProperty(self::PROPERTY_VISIBLE, $visible);
    }
}
