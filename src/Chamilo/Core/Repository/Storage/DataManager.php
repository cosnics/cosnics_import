<?php

namespace Chamilo\Core\Repository\Storage;

use ArrayIterator;
use Chamilo\Configuration\Configuration;
use Chamilo\Configuration\Storage\DataClass\Registration;
use Chamilo\Core\Repository\ContentObject\PortfolioItem\Storage\DataClass\PortfolioItem;
use Chamilo\Core\Repository\Instance\Storage\DataClass\SynchronizationData;
use Chamilo\Core\Repository\Manager;
use Chamilo\Core\Repository\Publication\Service\PublicationAggregator;
use Chamilo\Core\Repository\Publication\Service\PublicationAggregatorInterface;
use Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Storage\DataClass\ContentObjectAttachment;
use Chamilo\Core\Repository\Storage\DataClass\RepositoryCategory;
use Chamilo\Core\Repository\Workspace\Architecture\WorkspaceInterface;
use Chamilo\Core\Repository\Workspace\PersonalWorkspace;
use Chamilo\Core\Repository\Workspace\Repository\ContentObjectRelationRepository;
use Chamilo\Core\Repository\Workspace\Service\ContentObjectRelationService;
use Chamilo\Core\Repository\Workspace\Storage\DataClass\WorkspaceContentObjectRelation;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Interfaces\ComplexContentObjectSupport;
use Chamilo\Libraries\DependencyInjection\DependencyInjectionContainerBuilder;
use Chamilo\Libraries\Platform\Session\Session;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Storage\DataClass\Property\DataClassProperties;
use Chamilo\Libraries\Storage\DataClass\Property\DataClassProperty;
use Chamilo\Libraries\Storage\Parameters\DataClassCountGroupedParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassCountParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassDistinctParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrieveParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Parameters\RecordRetrieveParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\ComparisonCondition;
use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\Condition\NotCondition;
use Chamilo\Libraries\Storage\Query\Condition\OrCondition;
use Chamilo\Libraries\Storage\Query\GroupBy;
use Chamilo\Libraries\Storage\Query\Join;
use Chamilo\Libraries\Storage\Query\Joins;
use Chamilo\Libraries\Storage\Query\OrderBy;
use Chamilo\Libraries\Storage\Query\OrderProperty;
use Chamilo\Libraries\Storage\Query\Variable\FunctionConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\OperationConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Utilities\StringUtilities;

class DataManager extends \Chamilo\Libraries\Storage\DataManager\DataManager
{
    const ACTION_COUNT = 1;

    const ACTION_RETRIEVES = 2;

    const PREFIX = 'repository_';

    private static $applications = [];

    private static $helper_types;

    private static $number_of_categories;

    private static $registered_types;

    private static $workspace_has_categories;

    public static function activate_content_object_type($type)
    {
        $conditions = [];
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_TYPE),
            new StaticConditionVariable(ClassnameUtilities::getInstance()->getPackageNameFromNamespace($type))
        );
        $conditions[] = new InCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_STATE),
            ContentObject::get_inactive_status_types()
        );
        $condition = new AndCondition($conditions);

        $properties = [];
        $properties[] = new DataClassProperty(new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_STATE),
            new OperationConditionVariable(
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_STATE),
                OperationConditionVariable::MINUS, new StaticConditionVariable(ContentObject::STATE_INACTIVE)
            ));

        return self::updates(ContentObject::class, new DataClassProperties($properties), $condition);
    }

    public static function check_category_name(WorkspaceInterface $workspace, $parent_id, $category_name)
    {
        $conditions = [];
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_NAME),
            new StaticConditionVariable($category_name)
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_PARENT),
            new StaticConditionVariable($parent_id)
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_TYPE_ID),
            new StaticConditionVariable($workspace->getId())
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_TYPE),
            new StaticConditionVariable($workspace->getWorkspaceType())
        );
        $condition = new AndCondition($conditions);

        return self::count(RepositoryCategory::class, new DataClassCountParameters($condition)) > 0;
    }

    /**
     * Checks if there is a complex content object item for the given ref and parent id
     *
     * @param $ref_id int
     * @param $parent_id int
     */
    public static function complex_content_object_item_exists_for_ref_and_parent($ref_id, $parent_id)
    {
        $conditions = [];

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem::class, ComplexContentObjectItem::PROPERTY_REF
            ), new StaticConditionVariable($ref_id)
        );

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem::class, ComplexContentObjectItem::PROPERTY_PARENT
            ), new StaticConditionVariable($parent_id)
        );

        $condition = new AndCondition($conditions);

        return (self::count_complex_content_object_items(
                ComplexContentObjectItem::class, new DataClassCountParameters($condition)
            ) > 0);
    }

    public static function content_object_deletion_allowed($object, $only_version = false)
    {
        if ($object->get_owner_id() == 0)
        {
            return true;
        }

        // // Home block content object display
        // $formats = [];
        // $formats[] = 's:10:"use_object";i:' . (int) $object->get_id() . ';';
        // $formats[] = 's:10:"use_object";s:' . strlen((string) $object->get_id()) . ':"' . $object->get_id() . '";';
        //
        // $conditions = [];
        //
        // foreach ($formats as $format)
        // {
        // $conditions[] = new ContainsCondition(
        // new PropertyConditionVariable(Element::class, Element::PROPERTY_CONFIGURATION),
        // $format);
        // }
        //
        // $condition = new OrCondition($conditions);
        //
        // $usedInBlocks = \Chamilo\Core\Home\Storage\DataManager::count(
        // Block::class,
        // new DataClassCountParameters($condition));
        //
        // if ($usedInBlocks > 0)
        // {
        // return false;
        // }

        if ($only_version)
        {
            if ($object->has_attachers($only_version))
            {
                return false;
            }
            $forbidden = [];
            $forbidden[] = $object->get_id();
        }
        else
        {
            if ($object->has_attachers($only_version))
            {
                return false;
            }
            $children = [];
            // $children = self::getInstance()->get_children_ids($object);
            $versions = [];
            $versions = Datamanager::get_version_ids($object);
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

        $conditions = [];
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem::class, ComplexContentObjectItem::PROPERTY_REF
            ), new StaticConditionVariable($object->get_id())
        );

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem::class, ComplexContentObjectItem::PROPERTY_PARENT
            ), new StaticConditionVariable($object->get_id())
        );

        $condition = new OrCondition($conditions);
        $count_wrapper_items = self::count_complex_content_object_items(
            ComplexContentObjectItem::class, new DataClassCountParameters($condition)
        );

        if ($count_wrapper_items > 0)
        {
            return false;
        }

        $wrapper_types = self::get_active_helper_types();

        foreach ($wrapper_types as $wrapper_type)
        {
            // All wrapper types must have a 'reference_id' property!
            $count_wrapper_items = self::count_active_content_objects(
                $wrapper_type, new EqualityCondition(
                    new PropertyConditionVariable($wrapper_type, PortfolioItem::PROPERTY_REFERENCE),
                    new StaticConditionVariable($object->get_id())
                )
            );

            if ($count_wrapper_items > 0)
            {
                return false;
            }
        }

        $count_children = self::count_complex_content_object_items(
            ComplexContentObjectItem::class, new DataClassCountParameters(
                new EqualityCondition(
                    new PropertyConditionVariable(
                        ComplexContentObjectItem::class, ComplexContentObjectItem::PROPERTY_PARENT
                    ), new StaticConditionVariable($object->get_id())
                )
            )
        );

        if ($count_children > 0)
        {
            return false;
        }

        // Published in workspaces

        $contentObjectRelationService = new ContentObjectRelationService(new ContentObjectRelationRepository());
        $workspaceCount = $contentObjectRelationService->countWorkspacesForContentObject($object);

        if ($workspaceCount > 0)
        {
            return false;
        }

        return !self::getPublicationAggregator()->areContentObjectsPublished($forbidden);
    }

    public static function content_object_revert_allowed($object)
    {
        return !$object->is_latest_version();
    }

    public static function content_object_title_exists($title, $parent_id = null, $content_object_id = null)
    {
        $conditions = [];
        if (!is_null($parent_id))
        {
            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_PARENT_ID),
                new StaticConditionVariable($parent_id)
            );
        }
        if (!is_null($content_object_id))
        {
            $conditions[] = new NotCondition(
                new EqualityCondition(
                    new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_ID),
                    new StaticConditionVariable($content_object_id)
                )
            );
        }

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_TITLE),
            new StaticConditionVariable($title)
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_OWNER_ID),
            new StaticConditionVariable(Session::get_user_id())
        );
        $conditions[] = new InCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_TYPE),
            DataManager::get_registered_types()
        );
        $condition = new AndCondition($conditions);

        $parameters = new DataClassCountParameters($condition);

        return self::count_active_content_objects(ContentObject::class, $parameters) > 0;
    }

    public static function copy_complex_children($clo)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem::class, ComplexContentObjectItem::PROPERTY_PARENT
            ), new StaticConditionVariable($clo->get_id())
        );

        $items = self::retrieve_complex_content_object_items(ComplexContentObjectItem::class, $condition);

        foreach ($items as $item)
        {
            $nitem = new ComplexContentObjectItem();
            $nitem->set_user_id($item->get_user_id());
            $nitem->set_display_order($item->get_display_order());
            $nitem->set_parent($clo->get_id());
            $nitem->set_ref($item->get_ref());
            $nitem->create();

            $lo = self::retrieve_by_id(ContentObject::class, $item->get_ref());

            if ($lo instanceof ComplexContentObjectSupport)
            {
                $lo->create_all();
                $nitem->set_ref($lo->get_id());
                $nitem->update();
                self::copy_complex_content_object($lo);
            }
        }
    }

    public static function copy_complex_content_object($clo)
    {
        $clo->create_all();
        self::copy_complex_children($clo);

        return $clo;
    }

    public static function count_active_content_objects($type, $parameters = null)
    {
        return self::count_content_objects(
            $type, self::prepare_active_parameters(self::ACTION_COUNT, $type, $parameters)
        );
    }

    public static function count_complex_content_object_items($type, $parameters = null)
    {
        return self::count($type, $parameters);
    }

    public static function count_content_object_versions(ContentObject $object)
    {
        $parameters = new DataClassCountParameters();
        $parameters->set_condition(
            new EqualityCondition(
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_OBJECT_NUMBER),
                new StaticConditionVariable($object->get_object_number())
            )
        );

        return self::count_content_objects($object::class_name(), $parameters);
    }

    public static function count_content_objects($type, $parameters = null)
    {
        return self::count($type, self::prepare_parameters(self::ACTION_COUNT, $type, $parameters));
    }

    public static function count_doubles_in_repository(
        $condition = null, $count = null, $offset = null, $order_property = null
    )
    {
        return count(self::determine_doubles_in_repository($condition));
    }

    /**
     * Retrieves the relation object for a given user and content object
     *
     * @param $user_id type
     * @param $content_object_id type
     *
     * @return DataClass
     */
    public static function create_unique_category_name(WorkspaceInterface $workspace, $parent_id, $category_name)
    {
        $index = 0;
        $old_category_name = $category_name;
        while (self::check_category_name($workspace, $parent_id, $category_name))
        {
            $category_name = $old_category_name . ' (' . ++ $index . ')';
        }

        return $category_name;
    }

    public static function deactivate_content_object_type($type)
    {
        $conditions = [];
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_TYPE),
            new StaticConditionVariable(ClassnameUtilities::getInstance()->getPackageNameFromNamespace($type))
        );
        $conditions[] = new InCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_STATE),
            ContentObject::get_active_status_types()
        );
        $condition = new AndCondition($conditions);

        $properties = [];
        $properties[] = new DataClassProperty(new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_STATE),
            new OperationConditionVariable(
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_STATE),
                OperationConditionVariable::ADDITION, new StaticConditionVariable(ContentObject::STATE_INACTIVE)
            ));

        return self::updates(ContentObject::class, new DataClassProperties($properties), $condition);
    }

    /**
     * Deletes a category recursivly and unlinks all the contained content objects
     *
     * @param $category type
     * @param $fix_display_order type
     *
     * @return boolean
     */
    public static function delete_category_recursive(
        PublicationAggregatorInterface $publicationAggregator, $category, $fix_display_order = true
    )
    {
        $repository_data_manager = self::getInstance();
        $success = true;

        // Retrieve the objects and unlink them
        $condition = new EqualityCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_PARENT_ID),
            new StaticConditionVariable($category->get_id())
        );
        $content_objects = DataManager::retrieve_active_content_objects(ContentObject::class, $condition);

        foreach ($content_objects as $content_object)
        {
            $canUnlinkVersions = true;
            $versions = $content_object->get_content_object_versions();
            foreach ($versions as $version)
            {
                if (!$publicationAggregator->canContentObjectBeUnlinked($version))
                {
                    $canUnlinkVersions = false;
                    break;
                }
            }

            if (!$canUnlinkVersions)
            {
                $success = false;
                continue;
            }

            foreach ($versions as $version)
            {
                if (!$version->delete_links())
                {
                    $success = false;
                    continue;
                }
                if (!$version->move(0)) // move is needed, otherwise the rights
                    // locations will be removed
                {
                    $success = false;
                    continue;
                }
                if (!$version->recycle())
                {
                    $success = false;
                }
            }
        }

        if ($success)
        {
            // delete the category
            $condition = new EqualityCondition(
                new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_ID),
                new StaticConditionVariable($category->get_id())
            );
        }

        $success = self::deletes(RepositoryCategory::class, $condition);

        // the ordering should only be fixed on the top level (down levels are
        // always deleted)
        if ($fix_display_order)
        {
            // Correct the display order of the remaining categories
            self::fix_category_display_order($category);
        }

        // Delete all subcategories by recursively repeating the entire process
        $categories = DataManager::retrieves(
            RepositoryCategory::class, new DataClassRetrievesParameters(
                new EqualityCondition(
                    new PropertyConditionVariable(
                        RepositoryCategory::class, RepositoryCategory::PROPERTY_PARENT
                    ), new StaticConditionVariable($category->get_id())
                )
            )
        );

        foreach ($categories as $category)
        {
            if (!self::delete_category_recursive($publicationAggregator, $category, false))
            {
                $success = false;
            }
        }

        return $success;
    }

    public static function delete_content_object_by_user($user_id)
    {
        $content_object = DataManager::retrieve_content_object_by_user($user_id);
        foreach ($content_object as $object)
        {
            if (!self:: getPublicationAggregator()->deleteContentObjectPublications($object))
            {
                return false;
            }
            if (!$object->delete())
            {
                return false;
            }
        }

        return true;
    }

    public static function delete_workspace_category_recursive($category, $fix_display_order = true)
    {
        $succes = true;

        // Remove the relations
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                WorkspaceContentObjectRelation::class, WorkspaceContentObjectRelation::PROPERTY_CATEGORY_ID
            ), new StaticConditionVariable($category->get_id())
        );

        $succes = self::deletes(WorkspaceContentObjectRelation::class, $condition);

        // delete the category
        $condition = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_ID),
            new StaticConditionVariable($category->get_id())
        );
        $succes &= self::deletes(RepositoryCategory::class, $condition);

        // the ordering should only be fixed on the top level (down levels are
        // always deleted)
        if ($fix_display_order)
        {
            // Correct the display order of the remaining categories
            self::fix_category_display_order($category);
        }

        // Delete all subcategories by recursively repeating the entire process
        $categories = self::retrieves(
            RepositoryCategory::class, new DataClassRetrievesParameters(
                new EqualityCondition(
                    new PropertyConditionVariable(
                        RepositoryCategory::class, RepositoryCategory::PROPERTY_PARENT
                    ), new StaticConditionVariable($category->get_id())
                )
            )
        );
        foreach ($categories as $category)
        {
            if (!self::delete_workspace_category_recursive($category, false))
            {
                $succes = false;
            }
        }

        return $succes;
    }

    public static function determine_doubles_in_repository($condition = null)
    {
        $having = new ComparisonCondition(
            new FunctionConditionVariable(
                FunctionConditionVariable::COUNT,
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_CONTENT_HASH)
            ), ComparisonCondition::GREATER_THAN, new StaticConditionVariable(1)
        );

        $conditions = [];

        $conditions[] = new NotCondition(
            new EqualityCondition(
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_CURRENT),
                new StaticConditionVariable(ContentObject::CURRENT_OLD)
            )
        );

        if ($condition)
        {
            $conditions[] = $condition;
        }

        $condition = new AndCondition($conditions);

        $parameters = new DataClassCountGroupedParameters(
            $condition, new DataClassProperties(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_CONTENT_HASH)
        ), $having, null, new GroupBy([
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_CONTENT_HASH)]
            )
        );

        return self::count_grouped(ContentObject::class, $parameters);
    }

    private static function fix_category_display_order($category)
    {
        $conditions = [];
        $conditions[] = new ComparisonCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_DISPLAY_ORDER),
            ComparisonCondition::GREATER_THAN, new StaticConditionVariable($category->get_display_order())
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_PARENT),
            new StaticConditionVariable($category->get_parent())
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_TYPE_ID),
            new StaticConditionVariable($category->get_type_id())
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_TYPE),
            new StaticConditionVariable($category->getType())
        );
        $condition = new AndCondition($conditions);

        $properties = new DataClassProperties();
        $properties->add(new DataClassProperty(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_DISPLAY_ORDER),
            new OperationConditionVariable(
                new PropertyConditionVariable(
                    RepositoryCategory::class, RepositoryCategory::PROPERTY_DISPLAY_ORDER
                ), OperationConditionVariable::MINUS, new StaticConditionVariable(1)
            )
        ));

        self::updates(RepositoryCategory::class, $properties, $condition);
    }

    /**
     * @return PublicationAggregatorInterface
     */
    protected static function getPublicationAggregator()
    {
        $dependencyInjectionContainer = DependencyInjectionContainerBuilder::getInstance()->createContainer();

        return $dependencyInjectionContainer->get(PublicationAggregator::class);
    }

    public static function get_active_helper_types()
    {
        if (!isset(self::$helper_types))
        {

            self::$helper_types = array(
                'Chamilo\Core\Repository\ContentObject\PortfolioItem\Storage\DataClass\PortfolioItem'
            );
        }

        return self::$helper_types;
    }

    public static function get_number_of_categories($user_id)
    {
        if (!isset(self::$number_of_categories[$user_id]))
        {
            $condition = new EqualityCondition(
                new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_TYPE_ID),
                new StaticConditionVariable($user_id)
            );

            self::$number_of_categories[$user_id] = self::count(
                RepositoryCategory::class, new DataClassCountParameters($condition)
            );
        }

        return self::$number_of_categories[$user_id];
    }

    public static function get_registered_applications()
    {
        if (!isset(self::$applications) || count(self::$applications) == 0)
        {
            self::$applications = Application::get_active_packages();
        }

        return self::$applications;
    }

    public static function get_registered_types($show_active_only = true)
    {
        if (!(self::$registered_types))
        {
            $registrations = Configuration::registrations_by_type(
                Manager::package() . '\\ContentObject'
            );
            $types = [];

            foreach ($registrations as $registration)
            {
                if (!$show_active_only || $registration[Registration::PROPERTY_STATUS])
                {
                    $types[] = $registration[Registration::PROPERTY_CONTEXT] . '\Storage\DataClass\\' .
                        StringUtilities::getInstance()->createString(
                            $registration[Registration::PROPERTY_NAME]
                        )->upperCamelize();
                }
            }

            self::$registered_types = $types;
        }

        return self::$registered_types;
    }

    /**
     * retrieve category if the category does not exist, create a new category return the id
     */
    public static function get_repository_category_by_name_or_create_new(
        $user_id, $title, $parent_id = 0, $create_in_batch = false
    )
    {
        $conditions = [];
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_NAME),
            new StaticConditionVariable($title)
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_TYPE_ID),
            new StaticConditionVariable($user_id)
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_PARENT),
            new StaticConditionVariable($parent_id)
        );
        $condition = new AndCondition($conditions);

        $category = self::retrieve_categories($condition)->current();
        if (!$category)
        {
            $category = new RepositoryCategory();
            $category->set_type_id($user_id);
            $category->set_name($title);
            $category->set_parent($parent_id);
            $category->setType(PersonalWorkspace::WORKSPACE_TYPE);

            // Create category in database
            $category->create($create_in_batch);
        }

        return $category->get_id();
    }

    public static function get_used_disk_space($owner = null)
    {
        $types = DataManager::get_registered_types();
        $disk_space = 0;

        foreach ($types as $index => $type)
        {
            $class = $type;
            $properties = call_user_func(array($class, 'get_disk_space_properties'));

            if (is_null($properties))
            {
                continue;
            }

            if (!is_array($properties))
            {
                $properties = array($properties);
            }

            $sum = [];
            if (count($properties) == 1)
            {
                $property = new FunctionConditionVariable(
                    FunctionConditionVariable::SUM, new PropertyConditionVariable($class::class_name(), $properties[0]),
                    'disk_space'
                );
            }

            elseif (count($properties) == 2)
            {
                $left = new PropertyConditionVariable($class::class_name(), $properties[0]);
                $right = new PropertyConditionVariable($class::class_name(), $properties[1]);
                $property = new FunctionConditionVariable(
                    FunctionConditionVariable::SUM,
                    new OperationConditionVariable($left, OperationConditionVariable::ADDITION, $right), 'disk_space'
                );
            }
            else
            {
                $left = new PropertyConditionVariable($class::class_name(), $properties[0]);
                $i = 1;
                while (count($properties) > $i)
                {
                    $right = new PropertyConditionVariable($class::class_name(), $properties[$i]);
                    $operation = new OperationConditionVariable($left, OperationConditionVariable::ADDITION, $right);
                    $left = $operation;
                    $i ++;
                }
                $property = new FunctionConditionVariable(FunctionConditionVariable::SUM, $operation, 'disk_space');
            }
            $parameters = new RecordRetrieveParameters(new DataClassProperties($property));

            if ($owner)
            {
                $condition_owner = new EqualityCondition(
                    new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_OWNER_ID),
                    new StaticConditionVariable($owner)
                );
            }

            if ($class::isExtended())
            {
                if (isset($condition_owner))
                {
                    $parameters->set_condition($condition_owner);
                }
                $condition = new EqualityCondition(
                    new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_ID),
                    new PropertyConditionVariable($class::class_name(), $class::PROPERTY_ID)
                );
                $join = new Join(ContentObject::class, $condition);

                $parameters->set_joins(new Joins(array($join)));
            }
            else
            {
                $match = new EqualityCondition(
                    new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_TYPE),
                    new StaticConditionVariable($type)
                );

                if (isset($condition_owner))
                {
                    $parameters->set_condition(new AndCondition(array($match, $condition_owner)));
                }
                else
                {
                    $parameters->set_condition($match);
                }
            }
            $record = self::record($class::class_name(), $parameters);

            $disk_space += $record['disk_space'];
        }

        return $disk_space;
    }

    public static function get_version_ids($object)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_OBJECT_NUMBER),
            new StaticConditionVariable($object->get_object_number())
        );
        $parameters = new DataClassDistinctParameters(
            $condition, new DataClassProperties(
                array(new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_ID))
            )
        );
        $version_ids = self::distinct(ContentObject::class, $parameters);
        sort($version_ids);

        return $version_ids;
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
        if (self::complex_content_object_item_exists_for_ref_and_parent($check_content_object_id, $content_object_id))
        {
            return true;
        }

        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem::class, ComplexContentObjectItem::PROPERTY_PARENT
            ), new StaticConditionVariable($content_object_id)
        );
        $complex_content_object_items = self::retrieve_complex_content_object_items(
            ComplexContentObjectItem::class, $condition
        );
        foreach ($complex_content_object_items as $complex_content_object_item)
        {
            if (self::is_child_of_content_object($complex_content_object_item->get_ref(), $check_content_object_id))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks wheter the given type is a helper type
     *
     * @param $type String
     *
     * @return boolean
     */
    public static function is_helper_type($type)
    {
        $helper_types = self::get_active_helper_types();

        return in_array($type, $helper_types);
    }

    /**
     * Checks if the attachment id is attached to the given content object id
     *
     * @param $attachment_id type
     * @param $content_object_id type
     *
     * @return Boolean
     */
    public static function is_object_attached_to($attachment_id, $content_object_id)
    {
        $conditions = [];
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment::class, ContentObjectAttachment::PROPERTY_ATTACHMENT_ID
            ), new StaticConditionVariable($attachment_id)
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment::class, ContentObjectAttachment::PROPERTY_CONTENT_OBJECT_ID
            ), new StaticConditionVariable($content_object_id)
        );
        $condition = new AndCondition($conditions);

        $number_of_attachments = self::count(
            ContentObjectAttachment::class, new DataClassCountParameters($condition)
        );
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
     * Moves the given content object to a new parent
     *
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param int $newParentId
     *
     * @return bool
     */
    public static function moveContentObjectToNewParent(ContentObject $contentObject, $newParentId = 0)
    {
        $properties = new DataClassProperties();
        $properties->add(
            new DataClassProperty(
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_PARENT_ID),
                new StaticConditionVariable($newParentId)
            )
        );

        $condition = new EqualityCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_ID),
            new StaticConditionVariable($contentObject->getId())
        );

        return self::updates(ContentObject::class, $properties, $condition);
    }

    private static function prepare_active_parameters($action, $type, $parameters = null)
    {
        $condition = new NotCondition(
            new EqualityCondition(
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_CURRENT),
                new StaticConditionVariable(ContentObject::CURRENT_OLD)
            )
        );

        if (($parameters instanceof DataClassCountParameters && $action == self::ACTION_COUNT) ||
            ($parameters instanceof DataClassRetrievesParameters && $action == self::ACTION_RETRIEVES))
        {
            $parameters->set_condition(new AndCondition([$parameters->get_condition(), $condition]));
        }
        else
        {
            if ($parameters instanceof Condition)
            {
                $condition = new AndCondition([$condition, $parameters]);
            }

            if ($action == self::ACTION_COUNT)
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

    public static function prepare_parameters($action, $type, $parameters = null)
    {
        if ($parameters->get_condition() instanceof Condition)
        {
            $parameters->set_condition(
                new AndCondition(
                    [
                        $parameters->get_condition(),
                        new InCondition(
                            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_STATE),
                            ContentObject::get_active_status_types()
                        )
                    ]
                )
            );
        }
        else
        {
            $parameters->set_condition(
                new InCondition(
                    new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_STATE),
                    ContentObject::get_active_status_types()
                )
            );
        }

        return $parameters;
    }

    public static function retrieve_active_content_objects($type, $parameters = null)
    {
        return self::retrieve_content_objects(
            $type, self::prepare_active_parameters(self::ACTION_RETRIEVES, $type, $parameters)
        );
    }

    /**
     * Retrieves the best suited candidate for the most recent version of a content object, based on a content object
     * number
     *
     * @param string $objectNumber
     *
     * @return ContentObject
     */
    public static function retrieve_best_candidate_for_most_recent_content_object_version($objectNumber)
    {
        $conditions = [];
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_OBJECT_NUMBER),
            new StaticConditionVariable($objectNumber)
        );

        $condition = new AndCondition($conditions);
        $parameters = new DataClassRetrieveParameters(
            $condition, new OrderBy(array(
                new OrderProperty(
                    new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_ID), SORT_DESC
                )
            ))
        );

        return self::retrieve(ContentObject::class, $parameters);
    }

    public static function retrieve_categories($condition = null, $offset = null, $count = null, $orderBy = null)
    {
        if (!$orderBy instanceof OrderBy)
        {
            $orderBy = new OrderBy();
        }

        $orderBy->add(
            new OrderProperty(
                new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_PARENT)
            )
        );
        $orderBy->add(
            new OrderProperty(
                new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_DISPLAY_ORDER)
            )
        );

        $parameters = new DataClassRetrievesParameters($condition, $count, $offset, $orderBy);

        return self::retrieves(RepositoryCategory::class, $parameters);
    }

    public static function retrieve_complex_content_object_items($type, $parameters = null)
    {
        return self::retrieves($type, $parameters);
    }

    public static function retrieve_content_object_versions(ContentObject $object)
    {
        $parameters = new DataClassRetrievesParameters();
        $parameters->setCondition(
            new EqualityCondition(
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_OBJECT_NUMBER),
                new StaticConditionVariable($object->get_object_number())
            )
        );
        $parameters->setOrderBy(
            new OrderBy(array(
                    new OrderProperty(
                        new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_ID), SORT_DESC
                    )
                ))
        );

        return self::retrieve_content_objects($object::class_name(), $parameters);
    }

    public static function retrieve_content_objects($type, $parameters = null)
    {
        return self::retrieves($type, self::prepare_parameters(self::ACTION_RETRIEVES, $type, $parameters));
    }

    public static function retrieve_content_objects_by_user($user_id)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_OWNER_ID),
            new StaticConditionVariable($user_id)
        );
        $parameters = new DataClassRetrievesParameters($condition);

        return self::retrieve_content_objects(ContentObject::class, $parameters);
    }

    public static function retrieve_content_objects_for_user($user_id)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_OWNER_ID),
            new StaticConditionVariable($user_id)
        );

        return DataManager::retrieve_active_content_objects(ContentObject::class, $condition);
    }

    public static function retrieve_doubles_in_repository(
        $condition = null, $count = null, $offset = null, $order_property = null
    )
    {
        $double_counts = self::determine_doubles_in_repository($condition);

        $content_objects = [];

        foreach ($double_counts as $hash => $double_count)
        {
            $condition = new EqualityCondition(
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_CONTENT_HASH),
                new StaticConditionVariable($hash)
            );
            $content_objects[] = self::retrieve_active_content_objects(
                ContentObject::class, new DataClassRetrievesParameters($condition, 1)
            )->current();
        }

        // Sort the publication attributes
        if (count($order_property) > 0)
        {
            $order_column = $order_property[0]->get_property();
            $order_direction = $order_property[0]->get_direction();
            $ordering_values = [];

            foreach ($content_objects as $key => $content_object)
            {
                if ($order_column == 'Duplicates')
                {
                    $ordering_values[$key] = $double_counts[$content_object->get_content_hash()];
                }
                else
                {
                    $ordering_values[$key] = (string) strtolower($content_object->getDefaultProperty($order_column));
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

            $ordered_content_objects = [];

            foreach ($ordering_values as $key => $value)
            {
                $ordered_content_objects[] = $content_objects[$key];
            }

            $content_objects = $ordered_content_objects;
        }

        // Return the requested subset
        return new ArrayIterator(array_splice($content_objects, $offset, $count));
    }

    public static function retrieve_external_sync($condition)
    {
        $join = new Join(
            ContentObject::class, new EqualityCondition(
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_ID),
                new PropertyConditionVariable(
                    SynchronizationData::class, SynchronizationData::PROPERTY_CONTENT_OBJECT_ID
                )
            )
        );

        $parameters = new DataClassRetrieveParameters($condition);
        $parameters->set_joins(new Joins(array($join)));

        return self::retrieve(SynchronizationData::class, $parameters);
    }

    public static function retrieve_external_syncs($condition = null, $count = null, $offset = null, $order_by = null)
    {
        $join = new Join(
            ContentObject::class, new EqualityCondition(
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_ID),
                new PropertyConditionVariable(
                    SynchronizationData::class, SynchronizationData::PROPERTY_CONTENT_OBJECT_ID
                )
            )
        );

        $parameters = new DataClassRetrievesParameters($condition, $count, $offset, $order_by, new Joins(array($join)));

        return self::retrieves(SynchronizationData::class, $parameters);
    }

    public static function retrieve_most_recent_content_object_version($object)
    {
        $conditions = [];
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_OBJECT_NUMBER),
            new StaticConditionVariable($object->get_object_number())
        );

        $conditions[] = new NotCondition(
            new EqualityCondition(
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_CURRENT),
                new StaticConditionVariable(ContentObject::CURRENT_OLD)
            )
        );

        $condition = new AndCondition($conditions);
        $parameters = new DataClassRetrieveParameters(
            $condition, new OrderBy(array(
                    new OrderProperty(
                        new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_ID), SORT_DESC
                    )
                ))
        );

        return self::retrieve($object::class_name(), $parameters);
    }

    public static function retrieve_recycled_content_objects_from_category($category_id)
    {
        $conditions = [];
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_PARENT_ID),
            new StaticConditionVariable($category_id)
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_STATE),
            new StaticConditionVariable(ContentObject::STATE_RECYCLED)
        );
        $condition = new AndCondition($conditions);

        return DataManager::retrieve_active_content_objects(ContentObject::class, $condition);
    }

    public static function select_next_category_display_order($parent_category_id, $type_id, $type)
    {
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_PARENT),
            new StaticConditionVariable($parent_category_id)
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_TYPE_ID),
            new StaticConditionVariable($type_id)
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_TYPE),
            new StaticConditionVariable($type)
        );
        $condition = new AndCondition($conditions);

        return self::retrieve_next_value(
            RepositoryCategory::class, RepositoryCategory::PROPERTY_DISPLAY_ORDER, $condition
        );
    }

    public static function select_next_display_order($parent_id, $complex_type = null)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem::class, ComplexContentObjectItem::PROPERTY_PARENT
            ), new StaticConditionVariable($parent_id)
        );

        if (!is_null($complex_type))
        {
            $conditions = [];
            $conditions[] = $condition;
            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(
                    ComplexContentObjectItem::class, ComplexContentObjectItem::PROPERTY_TYPE
                ), new StaticConditionVariable($complex_type)
            );

            $condition = new AndCondition($conditions);
        }

        return self::retrieve_next_value(
            ComplexContentObjectItem::class, ComplexContentObjectItem::PROPERTY_DISPLAY_ORDER, $condition
        );
    }

    public static function workspace_has_categories(WorkspaceInterface $workspaceImplemention)
    {
        if (is_null(
            self::$workspace_has_categories[$workspaceImplemention->getWorkspaceType()][$workspaceImplemention->getId()]
        ))
        {
            $conditions = [];

            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_TYPE_ID),
                new StaticConditionVariable($workspaceImplemention->getId())
            );

            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_TYPE),
                new StaticConditionVariable($workspaceImplemention->getWorkspaceType())
            );

            $condition = new AndCondition($conditions);

            self::$workspace_has_categories[$workspaceImplemention->getWorkspaceType()][$workspaceImplemention->getId(
            )] = (self::count(
                    RepositoryCategory::class, new DataClassCountParameters($condition)
                ) > 0);
        }

        return self::$workspace_has_categories[$workspaceImplemention->getWorkspaceType(
        )][$workspaceImplemention->getId()];
    }
}
