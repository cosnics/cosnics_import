<?php
namespace Chamilo\Application\Weblcms\Admin\Extension\Platform\Entity\Helper;

use Chamilo\Application\Weblcms\Admin\Extension\Platform\Entity\PlatformGroupEntity;
use Chamilo\Application\Weblcms\Admin\Extension\Platform\Manager;
use Chamilo\Application\Weblcms\Admin\Extension\Platform\Storage\DataClass\Admin;
use Chamilo\Core\Group\Storage\DataClass\Group;
use Chamilo\Libraries\Format\Table\Column\DataClassPropertyTableColumn;
use Chamilo\Libraries\Format\Table\Column\StaticTableColumn;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Chamilo\Libraries\Storage\Parameters\DataClassCountParameters;
use Chamilo\Libraries\Storage\Parameters\RetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Join;
use Chamilo\Libraries\Storage\Query\Joins;
use Chamilo\Libraries\Storage\Query\RetrieveProperties;
use Chamilo\Libraries\Storage\Query\Variable\FunctionConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\PropertiesConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Translation\Translation;

class PlatformGroupEntityHelper
{
    public const PROPERTY_PATH = 'path';

    /**
     * Get the fully qualified class name of the object
     *
     * @return string
     */
    public static function class_name()
    {
        return get_called_class();
    }

    /**
     * Counts the data
     *
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     *
     * @return int
     */
    public function count_table_data($condition)
    {
        $join = new Join(
            Admin::class, new EqualityCondition(
                new PropertyConditionVariable(
                    Group::class, Group::PROPERTY_ID
                ), new PropertyConditionVariable(Admin::class, Admin::PROPERTY_ENTITY_ID)
            )
        );
        $joins = new Joins([$join]);

        $parameters = new DataClassCountParameters(
            $condition, $joins, new RetrieveProperties(
                [
                    new FunctionConditionVariable(
                        FunctionConditionVariable::DISTINCT, new PropertyConditionVariable(
                            Group::class, Group::PROPERTY_ID
                        )
                    )
                ]
            )
        );

        return DataManager::count(
            Group::class, $parameters
        );
    }

    public static function expand($entity_id)
    {
        $entities = [];

        $group = DataManager::retrieve_by_id(
            Group::class, $entity_id
        );

        if ($group instanceof Group)
        {
            $parents = $group->get_parents();

            foreach ($parents as $parent)
            {
                $entities[PlatformGroupEntity::ENTITY_TYPE][] = $parent;
            }
        }

        return $entities;
    }

    public static function get_table_columns()
    {
        $translator = Translation::getInstance();

        $columns = [];
        $columns[] = new DataClassPropertyTableColumn(
            Group::class, Group::PROPERTY_NAME,
            $translator->getTranslation('Name', [], \Chamilo\Core\Group\Manager::CONTEXT)
        );
        $columns[] = new StaticTableColumn(
            self::PROPERTY_PATH, $translator->getTranslation('Path', [], \Chamilo\Core\Group\Manager::CONTEXT)
        );
        $columns[] = new DataClassPropertyTableColumn(
            Group::class, Group::PROPERTY_CODE,
            $translator->getTranslation('Code', [], \Chamilo\Core\Group\Manager::CONTEXT)
        );

        return $columns;
    }

    public static function render_table_cell($renderer, $column, $result)
    {
        switch ($column->get_name())
        {
            case Group::PROPERTY_NAME :
                $url = $renderer->getUrlGenerator()->fromRequest(
                    [
                        Manager::PARAM_ACTION => Manager::ACTION_TARGET,
                        Manager::PARAM_ENTITY_TYPE => $renderer->application->get_selected_entity_type(),
                        Manager::PARAM_ENTITY_ID => $result[DataClass::PROPERTY_ID]
                    ]
                );

                return '<a href="' . $url . '">' . $result[Group::PROPERTY_NAME] . '</a>';
                break;
            case self::PROPERTY_PATH :
                $group = DataManager::retrieve_by_id(
                    Group::class, $result[Group::PROPERTY_ID]
                );

                return $group->get_fully_qualified_name();
                break;
            default :
                return null;
        }
    }

    /**
     * Returns the data as a resultset
     *
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     * @param $condition
     * @param int $offset
     * @param int $count
     * @param \Chamilo\Libraries\Storage\Query\OrderBy $order_property
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public static function retrieve_table_data($condition, $count, $offset, $order_property)
    {
        $join = new Join(
            Admin::class, new EqualityCondition(
                new PropertyConditionVariable(
                    Group::class, Group::PROPERTY_ID
                ), new PropertyConditionVariable(Admin::class, Admin::PROPERTY_ENTITY_ID)
            )
        );
        $joins = new Joins([$join]);

        $properties = new RetrieveProperties();
        $properties->add(
            new FunctionConditionVariable(
                FunctionConditionVariable::DISTINCT, new PropertiesConditionVariable(Group::class)
            )
        );

        $parameters = new RetrievesParameters(
            condition: $condition, count: $count, offset: $offset, orderBy: $order_property, joins: $joins,
            retrieveProperties: $properties
        );

        return DataManager::records(
            Group::class, $parameters
        );
    }
}
