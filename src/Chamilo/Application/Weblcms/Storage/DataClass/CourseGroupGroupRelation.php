<?php
namespace Chamilo\Application\Weblcms\Storage\DataClass;

use Chamilo\Application\Weblcms\Manager;
use Chamilo\Application\Weblcms\Storage\DataManager;
use Chamilo\Application\Weblcms\Tool\Implementation\CourseGroup\Storage\DataClass\CourseGroup;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 * Description of course_group_group_relation When several course_groups are created together they are refered as
 * course_group_group The relation is defined in the weblcml_course_group_group_relation table.
 *
 * @author shoira
 */
class CourseGroupGroupRelation extends DataClass
{
    public const CONTEXT = Manager::CONTEXT;

    public const PROPERTY_COURSE_CODE = 'course_id';
    public const PROPERTY_DOCUMENT_PUBLICATION_CATEGORY_ID = 'document_publication_category_id';
    public const PROPERTY_FORUM_PUBLICATION_CATEGORY_ID = 'forum_publication_category_id';
    public const PROPERTY_MAX_NUMBER_OF_COURSE_GROUP_PER_MEMBER = 'max_number_of_course_group_per_member';
    public const PROPERTY_NAME = 'group_name';

    private $defaultProperties;

    /**
     * Creates a new course_group group relation object.
     *
     * @param $id                int The numeric ID of the course_group group relation object. May be omitted if
     *                           creating a new object.
     * @param $defaultProperties array The default properties of the course_goup group relation object. Associative
     *                           array.
     */
    public function __construct($defaultProperties = [])
    {
        $this->defaultProperties = $defaultProperties;
    }

    public function category_exists($tool)
    {
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublicationCategory::class, ContentObjectPublicationCategory::PROPERTY_NAME
            ), new StaticConditionVariable($this->get_name())
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublicationCategory::class, ContentObjectPublicationCategory::PROPERTY_COURSE
            ), new StaticConditionVariable($this->get_course_code())
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublicationCategory::class, ContentObjectPublicationCategory::PROPERTY_TOOL
            ), new StaticConditionVariable($tool)
        );
        $condition = new AndCondition($conditions);

        $data_set = [];
        $data_set = DataManager::retrieve_content_object_publication_categories($condition, null, null, null);

        foreach ($data_set as $course_groups)
        {
            return true;
        }

        return false;
    }

    public function check_before_saving()
    {
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseGroup::class, CourseGroup::PROPERTY_GROUP_ID),
            new StaticConditionVariable($this->get_id())
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseGroup::class, CourseGroup::PROPERTY_COURSE_CODE),
            new StaticConditionVariable($this->get_course_code())
        );
        $condition = new AndCondition($conditions);
        $qty_course_groups = DataManager::count_course_groups($condition);

        if ($this->get_max_number_of_course_group_per_member() > $qty_course_groups)
        {
            $this->set_max_number_of_course_group_per_member($qty_course_groups);

            return true;
        }
        else
        {
            return true;
        }
    }

    public function count_course_groups()
    {
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseGroup::class, CourseGroup::PROPERTY_GROUP_ID),
            new StaticConditionVariable($this->get_id())
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseGroup::class, CourseGroup::PROPERTY_COURSE_CODE),
            new StaticConditionVariable($this->get_course_code())
        );
        $condition = new AndCondition($conditions);

        return DataManager::count_course_groups($condition);
    }

    /**
     * Creates the course_group group relation object in persistent storage
     *
     * @return bool
     */
    public function create(): bool
    {

        // if ($wdm->retrieve_course_group_group_relation_by_name($this->get_name()) == null)
        {
            $success = DataManager::create($this);

            if (!$success)
            {
                return false;
            }

            return true;
        }
        /*
         * else { return false; }
         */
    }

    public function create_course_group_category($tool)
    {
        if (!$this->category_exists($tool))
        {
            $content_object_publication_category = new ContentObjectPublicationCategory();
            $content_object_publication_category->set_parent('0');
            $content_object_publication_category->set_tool($tool);
            $content_object_publication_category->set_course($this->get_course_code());
            $content_object_publication_category->set_name($this->get_name());
            $content_object_publication_category->set_allow_change(0);
            $content_object_publication_category->set_display_order('1');
            $content_object_publication_category->create();
            switch ($tool)
            {
                case 'document' :
                    $this->set_document_publication_category_id($content_object_publication_category->get_id());
                    break;
                case 'forum' :
                    $this->set_forum_publication_category_id($content_object_publication_category->get_id());
                    break;
            }
            $this->update();
        }

        return $content_object_publication_category;
    }

    /**
     * Deletes the course group relation object from persistent storage
     *
     * @return bool
     */
    public function delete(): bool
    {
        $success = DataManager::delete_course_group_group_relation($this);
        if (!$success)
        {
            return false;
        }

        return true;
    }

    /**
     * Gets the course code of the course in which this course_group was created
     *
     * @return string
     */
    public function get_course_code()
    {
        return $this->getDefaultProperty(self::PROPERTY_COURSE_CODE);
    }

    public function get_course_groups_by_group_id()
    {
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseGroup::class, CourseGroup::PROPERTY_GROUP_ID),
            new StaticConditionVariable($this->get_id())
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseGroup::class, CourseGroup::PROPERTY_COURSE_CODE),
            new StaticConditionVariable($this->get_course_code())
        );
        $condition = new AndCondition($conditions);

        $data_set = [];
        $data_set = DataManager::retrieve_course_groups($condition, null, null, null);

        return $data_set;
    }

    /**
     * Gets the default properties of this course_group group relation object.
     *
     * @return array An associative array containing the properties.
     */
    public function getDefaultProperties(): array
    {
        return $this->defaultProperties;
    }

    public function setDefaultProperties(array $defaultProperties): CourseGroupGroupRelation
    {
        $this->defaultProperties = $defaultProperties;

        return $this;
    }

    /**
     * Gets a default property of this course_group group relation object by name.
     *
     * @param $name string The name of the property.
     */
    public function getDefaultProperty($name)
    {
        return $this->defaultProperties[$name];
    }

    /**
     * Get the default properties of all course user relations.
     *
     * @return array The property names.
     */
    public static function getDefaultPropertyNames(array $extendedPropertyNames = []): array
    {
        return [
            self::PROPERTY_COURSE_CODE,
            self::PROPERTY_ID,
            self::PROPERTY_MAX_NUMBER_OF_COURSE_GROUP_PER_MEMBER,
            self::PROPERTY_NAME,
            self::PROPERTY_DOCUMENT_PUBLICATION_CATEGORY_ID,
            self::PROPERTY_FORUM_PUBLICATION_CATEGORY_ID
        ];
    }

    /**
     * Gets the document_publication_id of this course_group group
     *
     * @return int
     */
    public function get_document_publication_category_id()
    {
        return $this->getDefaultProperty(self::PROPERTY_DOCUMENT_PUBLICATION_CATEGORY_ID);
    }

    /**
     * Gets the forum_publication_id of this course_group group
     *
     * @return int
     */
    public function get_forum_publication_category_id()
    {
        return $this->getDefaultProperty(self::PROPERTY_FORUM_PUBLICATION_CATEGORY_ID);
    }

    /**
     * Gets the group_id of this course_group
     *
     * @return int
     */
    public function get_id(): ?string
    {
        return $this->getDefaultProperty(self::PROPERTY_ID);
    }

    /**
     * Gets the max_number_of_course_group_per_member this course_group_group
     *
     * @return int
     */
    public function get_max_number_of_course_group_per_member()
    {
        return $this->getDefaultProperty(self::PROPERTY_MAX_NUMBER_OF_COURSE_GROUP_PER_MEMBER);
    }

    /**
     * Gets the group name of this course_group
     *
     * @return string
     */
    public function get_name()
    {
        return $this->getDefaultProperty(self::PROPERTY_NAME);
    }

    public function get_parent_id()
    {
        return $this->get_course_code();
    }

    /**
     * @return string
     */
    public static function getStorageUnitName(): string
    {
        return 'weblcms_course_group_group_relation';
    }

    public static function retrieve($id)
    {
        return DataManager::retrieve_course_group_group_relation($id);
    }

    public function set_course_code($code)
    {
        $this->setDefaultProperty(self::PROPERTY_COURSE_CODE, $code);
    }

    /**
     * Sets a default property of this course_group group relation object by name.
     *
     * @param $name  string The name of the property.
     * @param $value mixed The new value for the property.
     */
    public function setDefaultProperty($name, $value)
    {
        $this->defaultProperties[$name] = $value;
    }

    public function set_document_publication_category_id($id)
    {
        return $this->setDefaultProperty(self::PROPERTY_DOCUMENT_PUBLICATION_CATEGORY_ID, $id);
    }

    public function set_forum_publication_category_id($id)
    {
        return $this->setDefaultProperty(self::PROPERTY_FORUM_PUBLICATION_CATEGORY_ID, $id);
    }

    public function set_id(int $id): CourseGroupGroupRelation
    {
        return $this->setDefaultProperty(self::PROPERTY_ID, $id);
    }

    public function set_max_number_of_course_group_per_member($number)
    {
        return $this->setDefaultProperty(self::PROPERTY_MAX_NUMBER_OF_COURSE_GROUP_PER_MEMBER, $number);
    }

    public function set_name($name)
    {
        return $this->setDefaultProperty(self::PROPERTY_NAME, $name);
    }

    /**
     * checks whether the current user can subscribe to any of the relation's groups --> is only possible when the
     * maximum number of subscriptions does not exceed the actual number of subscription
     *
     * @param $user <type>
     *
     * @return bool $result
     */
    public function subcription_in_any_group_allowed($user)
    {
        // get all groups that belong to this relation
        $group_set = DataManager::retrieve_course_groups_by_course_group_group_relation_id(
            $this->get_course_code(), $this->get_id()
        );
        // check if current user is subscribed in any
        $current_subscriptions = 0;
        foreach ($group_set as $group)
        {
            if ($group->is_member($user))
            {
                $current_subscriptions ++;
            }
        }

        // if number of subscriptions => max number of allowed subscription -->
        // return false
        // else --> return true
        $max_subscriptions = $this->get_max_number_of_course_group_per_member();
        if ($current_subscriptions >= $max_subscriptions)
        {
            $result = false;
        }
        else
        {
            $result = true;
        }

        return $result;
    }

    /**
     * Updates the course_group group relation object in persistent storage
     *
     * @return bool
     */
    public function update(): bool
    {
        if ($this->check_before_saving())
        {
            $success = DataManager::update_course_group_group_relation($this);

            if (!$success)
            {
                return false;
            }

            return true;
        }

        return false;
    }
}
