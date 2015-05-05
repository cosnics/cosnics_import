<?php
namespace Chamilo\Core\Repository\Storage;

use Chamilo\Core\Group\Storage\DataClass\Group;
use Chamilo\Core\Home\Storage\DataClass\BlockConfiguration;
use Chamilo\Core\Repository\ContentObject\LearningPathItem\Storage\DataClass\LearningPathItem;
use Chamilo\Core\Repository\Instance\Storage\DataClass\SynchronizationData;
use Chamilo\Core\Repository\Manager;
use Chamilo\Core\Repository\RepositoryRights;
use Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Storage\DataClass\ContentObjectAttachment;
use Chamilo\Core\Repository\Storage\DataClass\ContentObjectGroupShare;
use Chamilo\Core\Repository\Storage\DataClass\ContentObjectRelTag;
use Chamilo\Core\Repository\Storage\DataClass\ContentObjectTag;
use Chamilo\Core\Repository\Storage\DataClass\ContentObjectUserShare;
use Chamilo\Core\Repository\Storage\DataClass\RepositoryCategory;
use Chamilo\Core\Repository\Storage\DataClass\SharedContentObjectRelCategory;
use Chamilo\Core\Rights\Entity\PlatformGroupEntity;
use Chamilo\Core\Rights\Entity\UserEntity;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
// use Chamilo\Libraries\Architecture\Exceptions\ObjectNotExistException;
use Chamilo\Libraries\Architecture\Interfaces\ComplexContentObjectSupport;
use Chamilo\Libraries\Platform\Session\Session;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Storage\DataClass\Property\DataClassProperties;
use Chamilo\Libraries\Storage\DataClass\Property\DataClassProperty;
// use Chamilo\Libraries\Storage\Exception\DataClassNoResultException;
use Chamilo\Libraries\Storage\Parameters\DataClassCountGroupedParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassCountParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassDistinctParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrieveParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Parameters\RecordRetrieveParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\Condition\InequalityCondition;
use Chamilo\Libraries\Storage\Query\Condition\NotCondition;
use Chamilo\Libraries\Storage\Query\Condition\OrCondition;
use Chamilo\Libraries\Storage\Query\Join;
use Chamilo\Libraries\Storage\Query\Joins;
use Chamilo\Libraries\Storage\Query\OrderBy;
use Chamilo\Libraries\Storage\Query\Variable\FunctionConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\OperationConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Storage\ResultSet\ArrayResultSet;
use Chamilo\Libraries\Utilities\StringUtilities;
use Chamilo\Libraries\Utilities\Utilities;

class DataManager extends \Chamilo\Libraries\Storage\DataManager\DataManager
{
    const PREFIX = 'repository_';
    const ACTION_COUNT = 1;
    const ACTION_RETRIEVES = 2;

    private static $helper_types;

    private static $applications = array();

    private static $number_of_categories;

    private static $registered_types;

    private static $user_has_categories;

    public static function retrieve_content_object($id, $type = null)
    {
        return self :: retrieve_by_id(ContentObject :: class_name(), $id);

        // $condition = new EqualityCondition(
        // new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID),
        // new StaticConditionVariable($id));
        // $parameters = new DataClassRetrieveParameters($condition);

        // if (! isset($id) || strlen($id) == 0 || $id == DataClass :: NO_UID)
        // {
        // throw new DataClassNoResultException(ContentObject :: class_name(), $parameters);
        // }

        // if (is_null($type))
        // {
        // $type = self :: determine_content_object_type($id);
        // }

        // return self :: fetch_content_object($parameters, $type);
    }

    public static function retrieve_complex_content_object_item($id, $type = null)
    {
        return self :: retrieve_by_id(ComplexContentObjectItem :: class_name(), $id);

        // if (! isset($id) || strlen($id) == 0 || $id == DataClass :: NO_UID)
        // {
        // throw new DataClassNoResultException(
        // ContentObject :: class_name(),
        // DataClassRetrieveParameters :: generate((int) $id));
        // }

        // if (is_null($type))
        // {
        // $type = self :: determine_complex_content_object_item_type($id);
        // }

        // $condition = new EqualityCondition(
        // new PropertyConditionVariable(
        // ComplexContentObjectItem :: class_name(),
        // ComplexContentObjectItem :: PROPERTY_ID),
        // new StaticConditionVariable($id));

        // return self :: fetch_complex_content_object_item($condition, $type);
    }

    // private static function fetch_content_object($parameters, $type)
    // {
    // $condition = new InCondition(
    // new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_STATE),
    // ContentObject :: get_active_status_types());

    // if ($parameters->get_condition() instanceof Condition)
    // {
    // $condition = new AndCondition($parameters->get_condition(), $condition);
    // }
    // else
    // {
    // $parameters->set_condition($condition);
    // }

    // if ($type :: is_extended())
    // {
    // $join = new Join(
    // ContentObject :: class_name(),
    // new EqualityCondition(
    // new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID),
    // new PropertyConditionVariable($type, $type :: PROPERTY_ID)));
    // if ($parameters->get_joins() instanceof Joins)
    // {
    // $joins = $parameters->get_joins();
    // $joins->add($join);
    // $parameters->set_joins($joins);
    // }
    // else
    // {
    // $joins = new Joins(array($join));
    // $parameters->set_joins($joins);
    // }
    // }
    // return self :: retrieve($type, $parameters);
    // }

    // private static function fetch_complex_content_object_item($condition, $type)
    // {
    // if ($type :: is_extended())
    // {
    // $join = new Join(
    // ComplexContentObjectItem :: class_name(),
    // new EqualityCondition(
    // new PropertyConditionVariable(
    // ComplexContentObjectItem :: class_name(),
    // ComplexContentObjectItem :: PROPERTY_ID),
    // new PropertyConditionVariable($type, $type :: PROPERTY_ID)));
    // $joins = new Joins(array($join));
    // }
    // else
    // {
    // $joins = null;
    // }
    // $parameters = new DataClassRetrieveParameters($condition, array(), $joins);

    // return self :: retrieve($type, $parameters);
    // }

    // /**
    // * Get the type of the content object matching the given id.
    // *
    // * @param int $id The id of the content object.
    // * @return string The type string.
    // */
    // public static function determine_content_object_type($id)
    // {
    // $condition = new EqualityCondition(
    // new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID),
    // new StaticConditionVariable($id));
    // $parameters = new RecordRetrieveParameters(
    // new DataClassProperties(
    // array(new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_TYPE))),
    // $condition);
    // $type = self :: record(ContentObject :: class_name(), $parameters);
    // if (isset($type[ContentObject :: PROPERTY_TYPE]))
    // {
    // return $type[ContentObject :: PROPERTY_TYPE];
    // }
    // else
    // {
    // throw new ObjectNotExistException($id);
    // }
    // }

    // /**
    // * Get the type of the complex content object item matching the given id.
    // *
    // * @param int $id The id of the content object.
    // * @return string The type string.
    // */
    // public static function determine_complex_content_object_item_type($id)
    // {
    // $condition = new EqualityCondition(
    // new PropertyConditionVariable(
    // ComplexContentObjectItem :: class_name(),
    // ComplexContentObjectItem :: PROPERTY_ID),
    // new StaticConditionVariable($id));
    // $parameters = new RecordRetrieveParameters(
    // new DataClassProperties(
    // array(
    // new PropertyConditionVariable(
    // ComplexContentObjectItem :: class_name(),
    // ComplexContentObjectItem :: PROPERTY_TYPE))),
    // $condition);
    // $type = self :: record(ComplexContentObjectItem :: class_name(), $parameters);

    // if (isset($type[ComplexContentObjectItem :: PROPERTY_TYPE]))
    // {
    // return $type[ComplexContentObjectItem :: PROPERTY_TYPE];
    // }
    // else
    // {
    // throw new ObjectNotExistException(ComplexContentObjectItem :: class_name(), $id);
    // }
    // }
    public static function count_content_objects($type, $parameters = null)
    {
        return self :: count($type, self :: prepare_parameters(self :: ACTION_COUNT, $type, $parameters));
    }

    public static function count_complex_content_object_items($type, $parameters = null)
    {
        return self :: count($type, self :: prepare_complex_parameters(self :: ACTION_COUNT, $type, $parameters));
    }

    public static function retrieve_content_objects($type, $parameters = null)
    {
        return self :: retrieves($type, self :: prepare_parameters(self :: ACTION_RETRIEVES, $type, $parameters));
    }

    public static function retrieve_complex_content_object_items($type, $parameters = null)
    {
        return self :: retrieves(
            $type,
            self :: prepare_complex_parameters(self :: ACTION_RETRIEVES, $type, $parameters));
    }

    public static function retrieve_content_objects_by_user($user_id)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_OWNER_ID),
            new StaticConditionVariable($user_id));
        $parameters = new DataClassRetrievesParameters($condition);
        return self :: retrieve_content_objects(ContentObject :: class_name(), $parameters);
    }

    public static function retrieve_most_recent_content_object_version($object)
    {
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_OBJECT_NUMBER),
            new StaticConditionVariable($object->get_object_number()));

        $conditions[] = new NotCondition(
            new EqualityCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_CURRENT),
                new StaticConditionVariable(ContentObject :: CURRENT_OLD)));

        $condition = new AndCondition($conditions);
        $parameters = new DataClassRetrieveParameters(
            $condition,
            array(
                new OrderBy(
                    new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID),
                    SORT_DESC,
                    self :: get_alias(ContentObject :: get_table_name()))));
        return self :: retrieve($object :: class_name(), $parameters);
    }

    public static function prepare_parameters($action, $type, $parameters = null)
    {
        if (! is_null($type) && $type :: is_extended())
        {
            $type_join = new Join(
                ContentObject :: class_name(),
                new EqualityCondition(
                    new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID),
                    new PropertyConditionVariable($type, $type :: PROPERTY_ID)));

            if (($parameters instanceof DataClassCountParameters && $action == self :: ACTION_COUNT) ||
                 ($parameters instanceof DataClassRetrievesParameters && $action == self :: ACTION_RETRIEVES))
            {
                if ($parameters->get_joins() instanceof Joins)
                {
                    $already_exists = false;
                    foreach ($parameters->get_joins()->get() as $join)
                    {
                        if ($join->hash() == $type_join->hash())
                        {
                            $already_exists = true;
                        }
                    }
                    if (! $already_exists)
                    {
                        $parameters->get_joins()->add($type_join);
                    }
                }
                else
                {
                    $parameters->set_joins(new Joins(array($type_join)));
                }
            }
            else
            {
                if ($action == self :: ACTION_COUNT)
                {
                    $parameters = new DataClassCountParameters();
                }
                else
                {
                    $parameters = new DataClassRetrievesParameters();
                }
                $parameters->set_joins(new Joins(array($type_join)));
            }
        }
        elseif ($type != ContentObject :: class_name())
        {
            $condition = new EqualityCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_TYPE),
                new StaticConditionVariable($type));

            if (($parameters instanceof DataClassCountParameters && $action == self :: ACTION_COUNT) ||
                 ($parameters instanceof DataClassRetrievesParameters && $action == self :: ACTION_RETRIEVES))
            {
                if ($parameters->get_condition() instanceof Condition)
                {
                    $parameters->set_condition(new AndCondition($parameters->get_condition(), $condition));
                }
                else
                {
                    $parameters->set_condition($condition);
                }
            }
            else
            {
                if ($action == self :: ACTION_COUNT)
                {
                    $parameters = new DataClassCountParameters();
                }
                else
                {
                    $parameters = new DataClassRetrievesParameters();
                }
                $parameters->set_condition($condition);
            }
        }
        if ($parameters->get_condition() instanceof Condition)
        {
            $parameters->set_condition(
                new AndCondition(
                    $parameters->get_condition(),
                    new InCondition(
                        new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_STATE),
                        ContentObject :: get_active_status_types())));
        }
        else
        {
            $parameters->set_condition(
                new InCondition(
                    new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_STATE),
                    ContentObject :: get_active_status_types()));
        }
        return $parameters;
    }

    public static function prepare_complex_parameters($action, $type, $parameters = null)
    {
        if (! is_null($type) && $type :: is_extended())
        {
            $type_join = new Join(
                ComplexContentObjectItem :: class_name(),
                new EqualityCondition(
                    new PropertyConditionVariable(
                        ComplexContentObjectItem :: class_name(),
                        ComplexContentObjectItem :: PROPERTY_ID),
                    new PropertyConditionVariable($type, $type :: PROPERTY_ID)));
            if (($parameters instanceof DataClassCountParameters && $action == self :: ACTION_COUNT) ||
                 ($parameters instanceof DataClassRetrievesParameters && $action == self :: ACTION_RETRIEVES))
            {
                if ($parameters->get_joins() instanceof Joins)
                {
                    $already_exists = false;
                    foreach ($parameters->get_joins()->get() as $join)
                    {
                        if ($join->hash() == $type_join->hash())
                        {
                            $already_exists = true;
                        }
                    }
                    if (! $already_exists)
                    {
                        $parameters->get_joins()->add($type_join);
                    }
                }
                else
                {
                    $parameters->set_joins(new Joins(array($type_join)));
                }
            }
            else
            {
                if ($action == self :: ACTION_COUNT)
                {
                    $parameters = new DataClassCountParameters();
                }
                else
                {
                    $parameters = new DataClassRetrievesParameters();
                }
                $parameters->set_joins(new Joins(array($type_join)));
            }
        }

        return $parameters;
    }

    public static function count_active_content_objects($type, $parameters = null)
    {
        return self :: count_content_objects(
            $type,
            self :: prepare_active_parameters(self :: ACTION_COUNT, $type, $parameters));
    }

    public static function retrieve_active_content_objects($type, $parameters = null)
    {
        return self :: retrieve_content_objects(
            $type,
            self :: prepare_active_parameters(self :: ACTION_RETRIEVES, $type, $parameters));
    }

    private static function prepare_active_parameters($action, $type, $parameters = null)
    {
        $condition = new NotCondition(
            new EqualityCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_CURRENT),
                new StaticConditionVariable(ContentObject :: CURRENT_OLD)));

        if (($parameters instanceof DataClassCountParameters && $action == self :: ACTION_COUNT) ||
             ($parameters instanceof DataClassRetrievesParameters && $action == self :: ACTION_RETRIEVES))
        {
            $parameters->set_condition(new AndCondition($parameters->get_condition(), $condition));
        }
        else
        {
            if ($parameters instanceof Condition)
            {
                $condition = new AndCondition($condition, $parameters);
            }

            if ($action == self :: ACTION_COUNT)
            {
                $parameters = new DataClassCountParameters();
            }
            else
            {
                $parameters = new DataClassRetrievesParameters();
            }
            $parameters->set_condition($condition);
        }

        return $parameters;
    }

    public static function count_content_object_versions(ContentObject $object)
    {
        $parameters = new DataClassCountParameters();
        $parameters->set_condition(
            new EqualityCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_OBJECT_NUMBER),
                new StaticConditionVariable($object->get_object_number())));

        return self :: count_active_content_objects($object :: class_name(), $parameters);
    }

    public static function retrieve_content_object_versions(ContentObject $object)
    {
        $parameters = new DataClassRetrievesParameters();
        $parameters->set_condition(
            new EqualityCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_OBJECT_NUMBER),
                new StaticConditionVariable($object->get_object_number())));
        $parameters->set_order_by(
            array(
                new OrderBy(
                    new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID),
                    SORT_DESC,
                    self :: get_alias(ContentObject :: get_table_name()))));

        return self :: retrieve_content_objects($object :: class_name(), $parameters);
    }

    public static function retrieve_external_sync($condition)
    {
        $join = new Join(
            ContentObject :: class_name(),
            new EqualityCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID),
                new PropertyConditionVariable(
                    SynchronizationData :: class_name(),
                    SynchronizationData :: PROPERTY_CONTENT_OBJECT_ID)));

        $parameters = new DataClassRetrieveParameters($condition);
        $parameters->set_joins(new Joins(array($join)));

        return self :: retrieve(SynchronizationData :: class_name(), $parameters);
    }

    public static function retrieve_external_syncs($condition = null, $count = null, $offset = null, $order_by = array())
    {
        $join = new Join(
            ContentObject :: class_name(),
            new EqualityCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID),
                new PropertyConditionVariable(
                    SynchronizationData :: class_name(),
                    SynchronizationData :: PROPERTY_CONTENT_OBJECT_ID)));

        $parameters = new DataClassRetrievesParameters($condition, $count, $offset, $order_by, new Joins(array($join)));

        return self :: retrieves(SynchronizationData :: class_name(), $parameters);
    }

    public static function get_version_ids($object)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_OBJECT_NUMBER),
            new StaticConditionVariable($object->get_object_number()));
        $parameters = new DataClassDistinctParameters($condition, ContentObject :: PROPERTY_ID);
        $version_ids = self :: distinct(ContentObject :: class_name(), $parameters);
        sort($version_ids);
        return $version_ids;
    }

    public static function activate_content_object_type($type)
    {
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_TYPE),
            new StaticConditionVariable(ClassnameUtilities :: getInstance()->getPackageNameFromNamespace($type)));
        $conditions[] = new InCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_STATE),
            ContentObject :: get_inactive_status_types());
        $condition = new AndCondition($conditions);

        $properties = array();
        $properties[new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_STATE)] = new OperationConditionVariable(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_STATE),
            OperationConditionVariable :: MINUS,
            new StaticConditionVariable(ContentObject :: STATE_INACTIVE));
        return self :: updates(ContentObject :: class_name(), $properties, $condition);
    }

    public static function deactivate_content_object_type($type)
    {
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_TYPE),
            new StaticConditionVariable(ClassnameUtilities :: getInstance()->getPackageNameFromNamespace($type)));
        $conditions[] = new InCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_STATE),
            ContentObject :: get_active_status_types());
        $condition = new AndCondition($conditions);

        $properties = array();
        $properties[new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_STATE)] = new OperationConditionVariable(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_STATE),
            OperationConditionVariable :: ADDITION,
            new StaticConditionVariable(ContentObject :: STATE_INACTIVE));
        return self :: updates(ContentObject :: class_name(), $properties, $condition);
    }

    public static function content_object_title_exists($title, $parent_id = null, $content_object_id = null)
    {
        $conditions = array();
        if (! is_null($parent_id))
        {
            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_PARENT_ID),
                new StaticConditionVariable($parent_id));
        }
        if (! is_null($content_object_id))
        {
            $conditions[] = new NotCondition(
                new EqualityCondition(
                    new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID),
                    new StaticConditionVariable($content_object_id)));
        }

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_TITLE),
            new StaticConditionVariable($title));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_OWNER_ID),
            new StaticConditionVariable(Session :: get_user_id()));
        $conditions[] = new InCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_TYPE),
            DataManager :: get_registered_types());
        $condition = new AndCondition($conditions);

        $parameters = new DataClassCountParameters($condition);
        return self :: count_active_content_objects(ContentObject :: class_name(), $parameters) > 0;
    }

    public static function retrieve_shared_content_object_rel_categories($condition = null, $offset = null, $count = null,
        OrderBy $order_by = null)
    {
        $join = new Join(
            RepositoryCategory :: class_name(),
            new EqualityCondition(
                new PropertyConditionVariable(
                    SharedContentObjectRelCategory :: class_name(),
                    SharedContentObjectRelCategory :: PROPERTY_CATEGORY_ID),
                new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_ID)));

        $parameters = new DataClassRetrievesParameters($condition, $count, $offset, $order_by, new Joins(array($join)));
        return self :: retrieves(SharedContentObjectRelCategory :: class_name(), $parameters);
    }

    public static function retrieve_shared_content_object_rel_category_for_user_and_content_object($user_id,
        $content_object_id)
    {
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                SharedContentObjectRelCategory :: class_name(),
                SharedContentObjectRelCategory :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($content_object_id));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_USER_ID),
            new StaticConditionVariable($user_id));
        $condition = new AndCondition($conditions);

        return self :: retrieve_shared_content_object_rel_categories($condition)->next_result();
    }

    public static function count_shared_content_objects(Condition $condition = null)
    {
        $join = new Join(
            SharedContentObjectRelCategory :: class_name(),
            new EqualityCondition(
                new PropertyConditionVariable(
                    SharedContentObjectRelCategory :: class_name(),
                    SharedContentObjectRelCategory :: PROPERTY_CONTENT_OBJECT_ID),
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID)),
            Join :: TYPE_LEFT);

        $parameters = new DataClassCountParameters($condition, new Joins(array($join)));
        return self :: count_active_content_objects(ContentObject :: class_name(), $parameters);
    }

    public static function retrieve_shared_content_objects(Condition $condition = null, $offset = null, $count = null,
        OrderBy $order_by = null)
    {
        $join = new Join(
            SharedContentObjectRelCategory :: class_name(),
            new EqualityCondition(
                new PropertyConditionVariable(
                    SharedContentObjectRelCategory :: class_name(),
                    SharedContentObjectRelCategory :: PROPERTY_CONTENT_OBJECT_ID),
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID)),
            Join :: TYPE_LEFT);

        $parameters = new DataClassRetrievesParameters($condition, $count, $offset, $order_by, new Joins(array($join)));

        return self :: retrieve_active_content_objects(ContentObject :: class_name(), $parameters);
    }

    public static function get_active_helper_types()
    {
        if (! isset(self :: $helper_types))
        {
            $types = array();

            $registrations = \Chamilo\Configuration\Storage\DataManager :: get_registrations_by_type(
                Manager :: package() . '\\ContentObject');
            foreach ($registrations as $registration)
            {
                if ($registration->get_category() == 'helper')
                {
                    $types[] = $registration->get_context() . '\Storage\DataClass\\' . StringUtilities :: getInstance()->createString(
                        $registration->get_name())->upperCamelize();
                }
            }

            self :: $helper_types = $types;
        }

        return self :: $helper_types;
    }

    public static function retrieve_categories($condition = null, $offset = null, $count = null, $order_property = null)
    {
        if ($order_property instanceof OrderBy)
        {
            $order_property = array($order_property);
        }

        $order_property[] = new OrderBy(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_PARENT));
        $order_property[] = new OrderBy(
            new PropertyConditionVariable(
                RepositoryCategory :: class_name(),
                RepositoryCategory :: PROPERTY_DISPLAY_ORDER));

        $parameters = new DataClassRetrievesParameters($condition, $count, $offset, $order_property);
        return self :: retrieves(RepositoryCategory :: class_name(), $parameters);
    }

    public static function select_next_category_display_order($parent_category_id, $user_id, $type)
    {
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_PARENT),
            new StaticConditionVariable($parent_category_id));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_USER_ID),
            new StaticConditionVariable($user_id));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_TYPE),
            new StaticConditionVariable($type));
        $condition = new AndCondition($conditions);

        return self :: retrieve_next_value(
            RepositoryCategory :: class_name(),
            RepositoryCategory :: PROPERTY_DISPLAY_ORDER,
            $condition);
    }

    public static function select_next_display_order($parent_id, $complex_type)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem :: class_name(),
                ComplexContentObjectItem :: PROPERTY_PARENT),
            new StaticConditionVariable($parent_id));

        if (! is_null($complex_type))
        {
            $conditions = array();
            $conditions[] = $condition;
            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(
                    ComplexContentObjectItem :: class_name(),
                    ComplexContentObjectItem :: PROPERTY_TYPE),
                new StaticConditionVariable($complex_type));

            $condition = new AndCondition($conditions);
        }

        return self :: retrieve_next_value(
            ComplexContentObjectItem :: class_name(),
            ComplexContentObjectItem :: PROPERTY_DISPLAY_ORDER,
            $condition);
    }

    public static function determine_doubles_in_repository($condition = null)
    {
        $having = new InequalityCondition(
            new FunctionConditionVariable(
                FunctionConditionVariable :: COUNT,
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_CONTENT_HASH)),
            InequalityCondition :: GREATER_THAN,
            new StaticConditionVariable(1));

        $conditions = array();

        $conditions[] = new NotCondition(
            new EqualityCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_CURRENT),
                new StaticConditionVariable(ContentObject :: CURRENT_OLD)));

        if ($condition)
        {
            $conditions[] = $condition;
        }

        $condition = new AndCondition($conditions);

        $parameters = new DataClassCountGroupedParameters(
            $condition,
            new DataClassProperties(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_CONTENT_HASH)),
            $having);

        return self :: count_grouped(ContentObject :: class_name(), $parameters);
    }

    public static function count_doubles_in_repository($condition = null, $count = null, $offset = null, $order_property = array())
    {
        return count(self :: determine_doubles_in_repository($condition));
    }

    public static function retrieve_doubles_in_repository($condition = null, $count = null, $offset = null,
        $order_property = array())
    {
        $double_counts = self :: determine_doubles_in_repository($condition);

        $content_objects = array();

        foreach ($double_counts as $hash => $double_count)
        {
            $condition = new EqualityCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_CONTENT_HASH),
                new StaticConditionVariable($hash));
            $content_objects[] = self :: retrieve_active_content_objects(
                ContentObject :: class_name(),
                new DataClassRetrievesParameters($condition, 1))->next_result();
        }

        // Sort the publication attributes
        if (count($order_property) > 0)
        {
            $order_column = $order_property[0]->get_property();
            $order_direction = $order_property[0]->get_direction();
            $ordering_values = array();

            foreach ($content_objects as $key => $content_object)
            {
                if ($order_column == 'Duplicates')
                {
                    $ordering_values[$key] = $double_counts[$content_object->get_content_hash()];
                }
                else
                {
                    $ordering_values[$key] = (string) strtolower($content_object->get_default_property($order_column));
                }
            }

            switch ($order_direction)
            {
                case SORT_ASC :
                    asort($ordering_values);
                    break;
                case SORT_DESC :
                    arsort($ordering_values);
                    break;
            }

            $ordered_content_objects = array();

            foreach ($ordering_values as $key => $value)
            {
                $ordered_content_objects[] = $content_objects[$key];
            }

            $content_objects = $ordered_content_objects;
        }

        // Return the requested subset
        return new ArrayResultSet(array_splice($content_objects, $offset, $count));
    }

    public static function get_used_disk_space($owner = null)
    {
        $types = DataManager :: get_registered_types();
        $disk_space = 0;

        foreach ($types as $index => $type)
        {
            $class = $type;
            $properties = call_user_func(array($class, 'get_disk_space_properties'));

            if (is_null($properties))
            {
                continue;
            }

            if (! is_array($properties))
            {
                $properties = array($properties);
            }

            $sum = array();
            if (count($properties) == 1)
            {
                $property = new FunctionConditionVariable(
                    FunctionConditionVariable :: SUM,
                    new PropertyConditionVariable($class :: class_name(), $properties[0]),
                    'disk_space');
            }

            elseif (count($properties) == 2)
            {
                $left = new PropertyConditionVariable($class :: class_name(), $properties[0]);
                $right = new PropertyConditionVariable($class :: class_name(), $properties[1]);
                $property = new FunctionConditionVariable(
                    FunctionConditionVariable :: SUM,
                    new OperationConditionVariable($left, OperationConditionVariable :: ADDITION, $right),
                    'disk_space');
            }
            else
            {
                $left = new PropertyConditionVariable($class :: class_name(), $properties[0]);
                $i = 1;
                while (count($properties) > $i)
                {
                    $right = new PropertyConditionVariable($class :: class_name(), $properties[$i]);
                    $operation = new OperationConditionVariable($left, OperationConditionVariable :: ADDITION, $right);
                    $left = $operation;
                    $i ++;
                }
                $property = new FunctionConditionVariable(FunctionConditionVariable :: SUM, $operation, 'disk_space');
            }
            $parameters = new RecordRetrieveParameters(new DataClassProperties($property));

            if ($owner)
            {
                $condition_owner = new EqualityCondition(
                    new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_OWNER_ID),
                    new StaticConditionVariable($owner));
            }

            if ($class :: is_extended())
            {
                if (isset($condition_owner))
                {
                    $parameters->set_condition($condition_owner);
                }
                $condition = new EqualityCondition(
                    new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID),
                    new PropertyConditionVariable($class :: class_name(), $class :: PROPERTY_ID));
                $join = new Join(ContentObject :: class_name(), $condition);

                $parameters->set_joins(new Joins(array($join)));
            }
            else
            {
                $match = new EqualityCondition(
                    new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_TYPE),
                    new StaticConditionVariable($type));

                if (isset($condition_owner))
                {
                    $parameters->set_condition(new AndCondition(array($match, $condition_owner)));
                }
                else
                {
                    $parameters->set_condition($match);
                }
            }
            $record = self :: record($class :: class_name(), $parameters);

            $disk_space += $record['disk_space'];
        }
        return $disk_space;
    }

    public static function get_registered_types($show_active_only = true)
    {
        if (! (self :: $registered_types))
        {
            $registrations = \Chamilo\Configuration\Storage\DataManager :: get_registrations_by_type(
                Manager :: package() . '\\ContentObject');
            $types = array();

            foreach ($registrations as $registration)
            {
                if (! $show_active_only || $registration->is_active())
                {
                    $types[] = $registration->get_context() . '\Storage\DataClass\\' . StringUtilities :: getInstance()->createString(
                        $registration->get_name())->upperCamelize();
                }
            }

            self :: $registered_types = $types;
        }
        return self :: $registered_types;
    }

    public static function content_object_deletion_allowed($object, $only_version = false)
    {
        if ($object->get_owner_id() == 0)
        {
            return true;
        }

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(BlockConfiguration :: class_name(), BlockConfiguration :: PROPERTY_VARIABLE),
            new StaticConditionVariable('use_object'));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(BlockConfiguration :: class_name(), BlockConfiguration :: PROPERTY_VALUE),
            new StaticConditionVariable($object->get_id()));
        $condition = new AndCondition($conditions);

        $blockinfos = \Chamilo\Core\Home\Storage\DataManager :: retrieves(
            BlockConfiguration :: class_name(),
            $condition);
        if ($blockinfos->size() > 0)
        {
            return false;
        }

        if ($only_version)
        {
            if ($object->has_attachers($only_version))
            {
                return false;
            }
            $forbidden = array();
            $forbidden[] = $object->get_id();
        }
        else
        {
            if ($object->has_attachers($only_version))
            {
                return false;
            }
            $children = array();
            // $children = self :: get_instance()->get_children_ids($object);
            $versions = array();
            $versions = Datamanager :: get_version_ids($object);
            $forbidden = array_merge($children, $versions);
        }

        if ($only_version)
        {
            if ($object->has_includers($only_version))
            {
                return false;
            }
        }
        else
        {
            if ($object->has_includers($only_version))
            {
                return false;
            }
        }
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem :: class_name(),
                ComplexContentObjectItem :: PROPERTY_REF),
            new StaticConditionVariable($object->get_id()));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem :: class_name(),
                ComplexContentObjectItem :: PROPERTY_PARENT),
            new StaticConditionVariable($object->get_id()));
        $condition = new OrCondition($conditions);
        $count_wrapper_items = self :: count_complex_content_object_items(
            ComplexContentObjectItem :: class_name(),
            $condition);
        if ($count_wrapper_items > 0)
        {
            return false;
        }

        $wrapper_types = self :: get_active_helper_types();

        foreach ($wrapper_types as $wrapper_type)
        {
            // All wrapper types must have a 'reference_id' property!
            $count_wrapper_items = self :: count_active_content_objects(
                $wrapper_type,
                new EqualityCondition(
                    new PropertyConditionVariable($wrapper_type, LearningPathItem :: PROPERTY_REFERENCE),
                    new StaticConditionVariable($object->get_id())));
            if ($count_wrapper_items > 0)
            {
                return false;
            }
        }

        $count_children = self :: count_complex_content_object_items(
            ComplexContentObjectItem :: class_name(),
            new EqualityCondition(
                new PropertyConditionVariable(
                    ComplexContentObjectItem :: class_name(),
                    ComplexContentObjectItem :: PROPERTY_PARENT),
                new StaticConditionVariable($object->get_id())));
        if ($count_children > 0)
        {
            return false;
        }

        return ! \Chamilo\Core\Repository\Publication\Storage\DataManager\DataManager :: any_content_object_is_published(
            $forbidden);
    }

    public static function copy_complex_content_object($clo)
    {
        $clo->create_all();
        self :: copy_complex_children($clo);
        return $clo;
    }

    public static function copy_complex_children($clo)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem :: class_name(),
                ComplexContentObjectItem :: PROPERTY_PARENT),
            new StaticConditionVariable($clo->get_id()));
        $items = self :: retrieve_complex_content_object_items(ComplexContentObjectItem :: class_name(), $condition);
        while ($item = $items->next_result())
        {
            $nitem = new ComplexContentObjectItem();
            $nitem->set_user_id($item->get_user_id());
            $nitem->set_display_order($item->get_display_order());
            $nitem->set_parent($clo->get_id());
            $nitem->set_ref($item->get_ref());
            $nitem->create();
            $lo = self :: retrieve_content_object($item->get_ref());
            if ($lo instanceof ComplexContentObjectSupport)
            {
                $lo->create_all();
                $nitem->set_ref($lo->get_id());
                $nitem->update();
                self :: copy_complex_content_object($lo);
            }
        }
    }

    public static function content_object_revert_allowed($object)
    {
        return ! $object->is_latest_version();
    }

    public static function delete_content_object_by_user($user_id)
    {
        $content_object = DataManager :: retrieve_content_object_by_user($user_id);
        while ($object = $content_object->next_result())
        {
            if (! \Chamilo\Core\Repository\Publication\Storage\DataManager\DataManager :: delete_content_object_publications(
                $object))
            {
                return false;
            }
            if (! $object->delete())
            {
                return false;
            }
        }
        return true;
    }

    public static function get_registered_applications()
    {
        if (! isset(self :: $applications) || count(self :: $applications) == 0)
        {
            self :: $applications = Application :: get_active_packages();
        }

        return self :: $applications;
    }

    public static function get_number_of_categories($user_id)
    {
        if (! isset(self :: $number_of_categories{$user_id}))
        {
            $condition = new EqualityCondition(
                new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_USER_ID),
                new StaticConditionVariable($user_id));
            // self :: get_instance()->number_of_categories{$user_id} = self ::
            // get_instance()->count_type_content_objects('category',
            // $condition);
            self :: $number_of_categories[$user_id] = self :: count(RepositoryCategory :: class_name(), $condition);
        }
        return self :: $number_of_categories{$user_id};
    }

    /**
     * retrieve category if the category does not exist, create a new category return the id
     */
    public static function get_repository_category_by_name_or_create_new($user_id, $title, $parent_id = 0,
        $create_in_batch = false)
    {
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_NAME),
            new StaticConditionVariable($title));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_USER_ID),
            new StaticConditionVariable($user_id));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_PARENT),
            new StaticConditionVariable($parent_id));
        $condition = new AndCondition($conditions);

        $category = self :: retrieve_categories($condition)->next_result();
        if (! $category)
        {
            $category = new RepositoryCategory();
            $category->set_user_id($user_id);
            $category->set_name($title);
            $category->set_parent($parent_id);
            $category->set_type(RepositoryCategory :: TYPE_NORMAL);

            // Create category in database
            $category->create($create_in_batch);
        }

        return $category->get_id();
    }

    /**
     * Checks wheter the given type is a helper type
     *
     * @param $type String
     * @return boolean
     */
    public static function is_helper_type($type)
    {
        $helper_types = self :: get_active_helper_types();
        return in_array($type, $helper_types);
    }

    /**
     *
     * @param $user User
     * @param $object ContentObject
     */
    public static function is_object_shared_with_user($user, $object)
    {
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectUserShare :: class_name(),
                ContentObjectUserShare :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($object->get_id()));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectUserShare :: class_name(),
                ContentObjectUserShare :: PROPERTY_USER_ID),
            new StaticConditionVariable($user->get_id()));
        $condition = new AndCondition($conditions);
        $count = self :: count(ContentObjectUserShare :: class_name(), $condition);
        if ($count > 0)
        {
            return true;
        }

        $groups = $user->get_groups(true);

        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectGroupShare :: class_name(),
                ContentObjectGroupShare :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($object->get_id()));
        $conditions[] = new InCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObjectGroupShare :: PROPERTY_GROUP_ID),
            $groups);
        $condition = new AndCondition($conditions);
        $count = self :: count(ContentObjectGroupShare :: class_name(), $condition);
        if ($count > 0)
        {
            return true;
        }

        return false;
    }

    public static function user_has_categories($user_id)
    {
        if (is_null(self :: $user_has_categories))
        {
            $condition = new EqualityCondition(
                new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_USER_ID),
                new StaticConditionVariable($user_id));
            self :: $user_has_categories = (self :: count(RepositoryCategory :: class_name(), $condition) > 0);
        }

        return self :: $user_has_categories;
    }

    public static function retrieve_content_objects_for_user($user_id)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_OWNER_ID),
            new StaticConditionVariable($user_id));
        return DataManager :: retrieve_active_content_objects(ContentObject :: class_name(), $condition);
    }

    public static function get_content_object_user_shares($content_object_id, $user_id)
    {
        $shares = RepositoryRights :: get_instance()->get_share_target_entities_overview(
            $content_object_id,
            RepositoryRights :: TYPE_USER_CONTENT_OBJECT,
            $user_id);
        $in_condition = new InCondition(
            new PropertyConditionVariable(User :: class_name(), User :: PROPERTY_ID),
            $shares[UserEntity :: ENTITY_TYPE]);

        return \Chamilo\Core\User\Storage\DataManager :: retrieves(
            \Chamilo\Core\User\Storage\DataClass\User :: class_name(),
            new DataClassRetrievesParameters($in_condition));
    }

    public static function get_content_object_group_shares($content_object_id, $user_id)
    {
        $shares = RepositoryRights :: get_instance()->get_share_target_entities_overview(
            $content_object_id,
            RepositoryRights :: TYPE_USER_CONTENT_OBJECT,
            $user_id);

        $in_condition = new InCondition(
            new PropertyConditionVariable(Group :: class_name(), Group :: PROPERTY_ID),
            $shares[PlatformGroupEntity :: ENTITY_TYPE]);

        return \Chamilo\Core\Group\Storage\DataManager :: retrieves(Group :: class_name(), $in_condition);
    }

    public static function count_content_object_user_shares($content_object_id, $user_id)
    {
        $shares = RepositoryRights :: get_instance()->get_share_target_entities_overview(
            $content_object_id,
            RepositoryRights :: TYPE_USER_CONTENT_OBJECT,
            $user_id);

        return count(array_unique($shares[UserEntity :: ENTITY_TYPE]));
    }

    public static function count_content_object_group_shares($content_object_id, $user_id)
    {
        $shares = RepositoryRights :: get_instance()->get_share_target_entities_overview(
            $content_object_id,
            RepositoryRights :: TYPE_USER_CONTENT_OBJECT,
            $user_id);

        return count(array_unique($shares[PlatformGroupEntity :: ENTITY_TYPE]));
    }

    public static function retrieve_recycled_content_objects_from_category($category_id)
    {
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_PARENT_ID),
            new StaticConditionVariable($category_id));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_STATE),
            new StaticConditionVariable(ContentObject :: STATE_RECYCLED));
        $condition = new AndCondition($conditions);

        return DataManager :: retrieve_active_content_objects(ContentObject :: class_name(), $condition);
    }

    /**
     * Checks if the attachment id is attached to the given content object id
     *
     * @param $attachment_id type
     * @param $content_object_id type
     * @return Boolean
     */
    public static function is_object_attached_to($attachment_id, $content_object_id)
    {
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_ATTACHMENT_ID),
            new StaticConditionVariable($attachment_id));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($content_object_id));
        $condition = new AndCondition($conditions);

        $number_of_attachments = self :: count(ContentObjectAttachment :: class_name(), $condition);
        if ($number_of_attachments > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Recursive method to check all the children (complex content object items) of a content object to see if the check
     * content object is a child of the given content object
     *
     * @param $content_object_id int
     * @param $check_content_object_id int
     *
     * @return boolean
     */
    public static function is_child_of_content_object($content_object_id, $check_content_object_id)
    {
        if (self :: complex_content_object_item_exists_for_ref_and_parent($check_content_object_id, $content_object_id))
        {
            return true;
        }

        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem :: class_name(),
                ComplexContentObjectItem :: PROPERTY_PARENT),
            new StaticConditionVariable($content_object_id));
        $complex_content_object_items = self :: retrieve_complex_content_object_items(
            ComplexContentObjectItem :: class_name(),
            $condition);
        while ($complex_content_object_item = $complex_content_object_items->next_result())
        {
            if (self :: is_child_of_content_object($complex_content_object_item->get_ref(), $check_content_object_id))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if there is a complex content object item for the given ref and parent id
     *
     * @param $ref_id int
     * @param $parent_id int
     */
    public static function complex_content_object_item_exists_for_ref_and_parent($ref_id, $parent_id)
    {
        $conditions = array();

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem :: class_name(),
                ComplexContentObjectItem :: PROPERTY_REF),
            new StaticConditionVariable($ref_id));

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem :: class_name(),
                ComplexContentObjectItem :: PROPERTY_PARENT),
            new StaticConditionVariable($parent_id));

        $condition = new AndCondition($conditions);

        return (self :: count_complex_content_object_items(ComplexContentObjectItem :: class_name(), $condition) > 0);
    }

    /**
     * Deletes a category recursivly and unlinks all the contained content objects
     *
     * @param $category type
     * @param $fix_display_order type
     * @return boolean
     */
    public static function delete_category_recursive($category, $fix_display_order = true)
    {
        $repository_data_manager = self :: get_instance();
        $succes = true;

        // Retrieve the objects and unlink them
        $condition = new EqualityCondition(
            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_PARENT_ID),
            new StaticConditionVariable($category->get_id()));
        $content_objects = DataManager :: retrieve_active_content_objects(ContentObject :: class_name(), $condition);

        while ($content_object = $content_objects->next_result())
        {
            $versions = $content_object->get_content_object_versions();
            foreach ($versions as $version)
            {
                if (! $version->delete_links())
                {
                    $succes = false;
                }
                if (! $version->move(0)) // move is needed, otherwise the rights
                                         // locations will be removed
                {
                    $succes = false;
                }
                if (! $version->recycle())
                {
                    $succes = false;
                }
            }
        }

        // delete the category
        $condition = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_ID),
            new StaticConditionVariable($category->get_id()));

        $succes = self :: deletes(RepositoryCategory :: class_name(), $condition);

        // the ordering should only be fixed on the top level (down levels are
        // always deleted)
        if ($fix_display_order)
        {
            // Correct the display order of the remaining categories
            self :: fix_category_display_order($category);
        }

        // Delete all subcategories by recursively repeating the entire process
        $categories = DataManager :: retrieves(
            RepositoryCategory :: class_name(),
            new EqualityCondition(
                new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_PARENT),
                new StaticConditionVariable($category->get_id())));

        while ($categories && $category = $categories->next_result())
        {
            if (! self :: delete_category_recursive($category, false))
            {
                $succes = false;
            }
        }

        return $succes;
    }

    public static function delete_share_category_recursive($category, $fix_display_order = true)
    {
        $succes = true;

        // Remove the relations
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                SharedContentObjectRelCategory :: class_name(),
                SharedContentObjectRelCategory :: PROPERTY_CATEGORY_ID),
            new StaticConditionVariable($category->get_id()));

        $succes = self :: deletes(SharedContentObjectRelCategory :: class_name(), $condition);

        // delete the category
        $condition = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_ID),
            new StaticConditionVariable($category->get_id()));
        $succes &= self :: deletes(RepositoryCategory :: class_name(), $condition);

        // the ordering should only be fixed on the top level (down levels are
        // always deleted)
        if ($fix_display_order)
        {
            // Correct the display order of the remaining categories
            self :: fix_category_display_order($category);
        }

        // Delete all subcategories by recursively repeating the entire process
        $categories = self :: retrieves(
            RepositoryCategory :: class_name(),
            new EqualityCondition(
                new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_PARENT),
                new StaticConditionVariable($category->get_id())));
        while ($category = $categories->next_result())
        {
            if (! self :: delete_share_category_recursive($category, false))
            {
                $succes = false;
            }
        }

        return $succes;
    }

    private static function fix_category_display_order($category)
    {
        $conditions = array();
        $conditions[] = new InequalityCondition(
            RepositoryCategory :: PROPERTY_DISPLAY_ORDER,
            InequalityCondition :: GREATER_THAN,
            $category->get_display_order());
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_PARENT),
            new StaticConditionVariable($category->get_parent()));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_USER_ID),
            new StaticConditionVariable($category->get_user_id()));
        $condition = new AndCondition($conditions);

        $properties = new DataClassProperty(
            new PropertyConditionVariable(
                RepositoryCategory :: class_name(),
                RepositoryCategory :: PROPERTY_DISPLAY_ORDER),
            new OperationConditionVariable(
                new PropertyConditionVariable(
                    RepositoryCategory :: class_name(),
                    RepositoryCategory :: PROPERTY_DISPLAY_ORDER),
                OperationConditionVariable :: MINUS,
                new StaticConditionVariable(1)));

        self :: updates(RepositoryCategory :: class_name(), $properties, $condition);
    }

    /**
     * Retrieves the relation object for a given user and content object
     *
     * @param $user_id type
     * @param $content_object_id type
     * @return DataClass
     */
    public static function create_unique_category_name($user_id, $parent_id, $category_name)
    {
        $index = 0;
        $old_category_name = $category_name;
        while (self :: check_category_name($user_id, $parent_id, $category_name))
        {
            $category_name = $old_category_name . ' (' . ++ $index . ')';
        }
        return $category_name;
    }

    public static function check_category_name($user_id, $parent_id, $category_name)
    {
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_NAME),
            new StaticConditionVariable($category_name));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_PARENT),
            new StaticConditionVariable($parent_id));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_USER_ID),
            new StaticConditionVariable($user_id));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory :: class_name(), RepositoryCategory :: PROPERTY_TYPE),
            new StaticConditionVariable(RepositoryCategory :: TYPE_NORMAL));
        $condition = new AndCondition($conditions);

        return self :: count(RepositoryCategory :: class_name(), $condition) > 0;
    }

    /**
     * Sets the tags for the given content objects.
     * New tags will be registered in the tag cloud of the given user
     *
     * @param array $tags
     * @param array $content_object_ids
     * @param int $user_id
     *
     * @throws \Exception
     */
    public static function set_tags_for_content_objects(array $tags, array $content_object_ids, $user_id)
    {
        self :: truncate_tags_for_content_objects($content_object_ids);
        self :: add_tags_to_content_objects($tags, $content_object_ids, $user_id);
    }

    /**
     * Truncates the tags for the given content objects
     *
     * @param array $content_object_ids
     *
     * @throws \Exception
     */
    public static function truncate_tags_for_content_objects(array $content_object_ids)
    {
        $condition = new InCondition(
            new PropertyConditionVariable(
                ContentObjectRelTag :: class_name(),
                ContentObjectRelTag :: PROPERTY_CONTENT_OBJECT_ID),
            $content_object_ids);

        if (! self :: deletes(ContentObjectRelTag :: class_name(), $condition))
        {
            throw new \Exception(
                Translation :: get(
                    'ObjectNotDeleted',
                    array('OBJECT' => Translation :: get('ContentObjectRelTag')),
                    Utilities :: COMMON_LIBRARIES));
        }
    }

    /**
     * Adds the given tags to the given content objects.
     * New tags will be registered in the tag cloud of the given user
     *
     * @param array $tags
     * @param array $content_object_ids
     * @param int $user_id
     *
     * @throws \Exception
     */
    public static function add_tags_to_content_objects(array $tags, array $content_object_ids, $user_id)
    {
        foreach ($tags as $tag)
        {
            $content_object_tag = self :: retrieve_or_create_content_object_tag_by_user_id_and_tag($user_id, $tag);

            $content_object_ids_for_tag = self :: retrieve_content_object_ids_for_tag($content_object_tag->get_id());

            foreach ($content_object_ids as $content_object_id)
            {
                if (in_array($content_object_id, $content_object_ids_for_tag))
                {
                    continue;
                }

                $content_object_rel_tag = new ContentObjectRelTag();
                $content_object_rel_tag->set_tag_id($content_object_tag->get_id());
                $content_object_rel_tag->set_content_object_id($content_object_id);

                if (! $content_object_rel_tag->create())
                {
                    throw new \Exception(
                        Translation :: get(
                            'ObjectNotCreated',
                            array('OBJECT' => Translation :: get('ContentObjectRelTag')),
                            Utilities :: COMMON_LIBRARIES));
                }
            }
        }
    }

    /**
     * Retrieves the content object ids that are connected to a given content object tag
     *
     * @param int $content_object_tag_id
     *
     * @return int
     */
    public static function retrieve_content_object_ids_for_tag($content_object_tag_id)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(ContentObjectRelTag :: class_name(), ContentObjectRelTag :: PROPERTY_TAG_ID),
            new StaticConditionVariable($content_object_tag_id));

        $content_object_ids = array();

        $content_object_rel_tags = self :: retrieves(ContentObjectRelTag :: class_name(), $condition);
        while ($content_object_rel_tag = $content_object_rel_tags->next_result())
        {
            $content_object_ids[] = $content_object_rel_tag->get_content_object_id();
        }

        return $content_object_ids;
    }

    /**
     * Retrieves a content object tag object by a given user_id and tag name.
     * If the content object tag does not exist
     * the system will automatically create a new one.
     *
     * @param int $user_id
     * @param string $tag
     *
     * @throws \Exception
     *
     * @return ContentObjectTag
     */
    public static function retrieve_or_create_content_object_tag_by_user_id_and_tag($user_id, $tag)
    {
        $conditions = array();

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObjectTag :: class_name(), ContentObjectTag :: PROPERTY_USER_ID),
            new StaticConditionVariable($user_id));

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObjectTag :: class_name(), ContentObjectTag :: PROPERTY_TAG),
            new StaticConditionVariable($tag));

        $condition = new AndCondition($conditions);

        $content_object_tag = self :: retrieve(ContentObjectTag :: class_name(), $condition);

        if (! $content_object_tag)
        {
            $content_object_tag = new ContentObjectTag();
            $content_object_tag->set_user_id($user_id);
            $content_object_tag->set_tag($tag);

            if (! $content_object_tag->create())
            {
                throw new \Exception(
                    Translation :: get(
                        'ObjectNotCreated',
                        array('OBJECT' => Translation :: get('ContentObjectTag')),
                        Utilities :: COMMON_LIBRARIES));
            }
        }

        return $content_object_tag;
    }

    /**
     * Retrieves the content object tags for a given content object
     *
     * @param int $content_object_id
     *
     * @return array
     */
    public static function retrieve_content_object_tags_for_content_object($content_object_id)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectRelTag :: class_name(),
                ContentObjectRelTag :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($content_object_id));

        $joins = new Joins();
        $joins->add(
            new Join(
                ContentObjectRelTag :: class_name(),
                new EqualityCondition(
                    new PropertyConditionVariable(
                        ContentObjectRelTag :: class_name(),
                        ContentObjectRelTag :: PROPERTY_TAG_ID),
                    new PropertyConditionVariable(ContentObjectTag :: class_name(), ContentObjectTag :: PROPERTY_ID))));

        $parameters = new DataClassRetrievesParameters($condition, null, null, array(), $joins);
        $content_object_tags = self :: retrieves(ContentObjectTag :: class_name(), $parameters);

        return self :: get_tags_from_result_set($content_object_tags);
    }

    /**
     * Retrieves the content object tags for a given user
     *
     * @param int $user_id
     *
     * @return array
     */
    public static function retrieve_content_object_tags_for_user($user_id)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(ContentObjectTag :: class_name(), ContentObjectTag :: PROPERTY_USER_ID),
            new StaticConditionVariable($user_id));

        $content_object_tags = self :: retrieves(ContentObjectTag :: class_name(), $condition);

        return self :: get_tags_from_result_set($content_object_tags);
    }

    /**
     * Parses the ContentObjectTag resultset and returns the tags as an array
     *
     * @param \libraries\storage\ResultSet $content_object_tags_resultset
     *
     * @return array
     */
    protected static function get_tags_from_result_set($content_object_tags_resultset)
    {
        $tags = array();

        while ($content_object_tag = $content_object_tags_resultset->next_result())
        {
            $tags[] = $content_object_tag->get_tag();
        }

        return $tags;
    }
}
