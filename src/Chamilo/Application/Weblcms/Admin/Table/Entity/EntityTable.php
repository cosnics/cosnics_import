<?php
namespace Chamilo\Application\Weblcms\Admin\Table\Entity;

use Chamilo\Libraries\Format\Table\Extension\RecordTable\RecordTable;
use Chamilo\Libraries\Format\Table\Interfaces\TableFormActionsSupport;
use Chamilo\Application\Weblcms\Admin\Manager;
use Chamilo\Libraries\Format\Table\FormAction\TableFormActions;
use Chamilo\Libraries\Format\Table\FormAction\TableFormAction;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;

/**
 * Table for the schema
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class EntityTable extends RecordTable implements TableFormActionsSupport
{
    const TABLE_IDENTIFIER = Manager :: PARAM_ENTITY_ID;

    /**
     * Returns the implemented form actions
     *
     * @return TableFormActions
     */
    public function get_implemented_form_actions()
    {
        $actions = new TableFormActions(__NAMESPACE__);

        $actions->add_form_action(
            new TableFormAction(
                array(Manager :: PARAM_ACTION => Manager :: ACTION_DELETE),
                Translation :: get('RemoveSelected', null, Utilities :: COMMON_LIBRARIES)));

        return $actions;
    }

    public function get_helper_class_name()
    {
        return $this->get_component()->get_selected_entity_class(true);
    }
}