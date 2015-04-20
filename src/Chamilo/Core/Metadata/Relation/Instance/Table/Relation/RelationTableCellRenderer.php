<?php
namespace Chamilo\Core\Metadata\Relation\Instance\Table\Relation;

use Chamilo\Core\Metadata\Relation\Instance\Manager;
use Chamilo\Libraries\Format\Structure\Toolbar;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Format\Table\Extension\DataClassTable\DataClassTableCellRenderer;
use Chamilo\Libraries\Format\Table\Interfaces\TableCellRendererActionsColumnSupport;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;

/**
 *
 * @package Chamilo\Core\Metadata\Relation\Instance\Table\Relation
 * @author Sven Vanpoucke - Hogeschool Gent
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class RelationTableCellRenderer extends DataClassTableCellRenderer implements TableCellRendererActionsColumnSupport
{

    // $this->add_column(new StaticTableColumn(self :: PROPERTY_SOURCE));
    // $this->add_column(new StaticTableColumn(self :: PROPERTY_RELATION));
    // $this->add_column(new StaticTableColumn(self :: PROPERTY_TARGET));

    /**
     *
     * @param \Chamilo\Libraries\Format\Table\Column\TableColumn $column
     * @param \Chamilo\Core\Metadata\Relation\Instance\Storage\DataClass\RelationInstance $relationInstance
     * @return string
     */
    public function render_cell($column, $relationInstance)
    {
        switch ($column->get_name())
        {
            case RelationTableColumnModel :: PROPERTY_SOURCE :
                return $this->renderEntityByTypeAndIdentifier(
                    $relationInstance->get_source_type(),
                    $relationInstance->get_source_id());
            case RelationTableColumnModel :: PROPERTY_TARGET :
                return $this->renderEntityByTypeAndIdentifier(
                    $relationInstance->get_target_type(),
                    $relationInstance->get_target_id());
            case RelationTableColumnModel :: PROPERTY_RELATION :
                return $relationInstance->getRelation()->get_name();
        }

        return parent :: render_cell($column, $relationInstance);
    }

    public function renderEntityByTypeAndIdentifier($entityType, $entityIdentifier)
    {
        if ($entityIdentifier == 0)
        {
            return $entityType;
        }
        else
        {
            $entity = \Chamilo\Libraries\Storage\DataManager\DataManager :: retrieve_by_id(
                $entityType,
                $entityIdentifier);

            return (string) $entity;
        }
    }

    /**
     * Returns the actions toolbar
     *
     * @param \Chamilo\Core\Metadata\Relation\Instance\Storage\DataClass\RelationInstance $relationInstance
     * @return string
     */
    public function get_actions($relationInstance)
    {
        $toolbar = new Toolbar(Toolbar :: TYPE_HORIZONTAL);

        $toolbar->add_item(
            new ToolbarItem(
                Translation :: get('Edit', null, Utilities :: COMMON_LIBRARIES),
                Theme :: getInstance()->getCommonImagePath('Action/Edit'),
                $this->get_component()->get_url(
                    array(
                        Manager :: PARAM_ACTION => Manager :: ACTION_UPDATE,
                        Manager :: PARAM_RELATION_INSTANCE_ID => $relationInstance->get_id())),
                ToolbarItem :: DISPLAY_ICON));

        $toolbar->add_item(
            new ToolbarItem(
                Translation :: get('Delete', null, Utilities :: COMMON_LIBRARIES),
                Theme :: getInstance()->getCommonImagePath('Action/Delete'),
                $this->get_component()->get_url(
                    array(
                        Manager :: PARAM_ACTION => Manager :: ACTION_DELETE,
                        Manager :: PARAM_RELATION_INSTANCE_ID => $relationInstance->get_id())),
                ToolbarItem :: DISPLAY_ICON,
                true));

        return $toolbar->as_html();
    }
}