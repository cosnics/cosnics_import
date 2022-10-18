<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\User\Component\SubSubscribedGroup;

use Chamilo\Core\Group\Storage\DataClass\Group;
use Chamilo\Core\User\Manager;
use Chamilo\Libraries\Format\Table\Column\TableColumn;
use Chamilo\Libraries\Format\Table\Extension\DataClassTable\DataClassTableCellRenderer;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * * *************************************************************************** Cell renderer for a course subgroup
 * browser table.
 * 
 * @author Stijn Van Hoecke ****************************************************************************
 */
class SubSubscribedPlatformGroupTableCellRenderer extends DataClassTableCellRenderer
{

    public function renderCell(TableColumn $column, $group): string
    {
        // Add special features here
        switch ($column->get_name())
        {
            // Exceptions that need post-processing go here ...
            
            case Group::PROPERTY_NAME :
                $title = parent::renderCell($column, $group);
                $title_short = $title;
                if (strlen($title_short) > 53)
                {
                    $title_short = mb_substr($title_short, 0, 50) . '&hellip;';
                }
                return $title_short;
            
            case Group::PROPERTY_DESCRIPTION :
                $description = strip_tags(parent::renderCell($column, $group));
                return StringUtilities::getInstance()->truncate($description);
            case Translation::get(
                SubSubscribedPlatformGroupTableColumnModel::USERS, 
                null, 
                Manager::context()) :
                return $group->count_users();
            case Translation::get(SubSubscribedPlatformGroupTableColumnModel::SUBGROUPS) :
                return $group->count_subgroups(true, true);
        }
        
        return parent::renderCell($column, $group);
    }
}
