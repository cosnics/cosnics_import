<?php
namespace Chamilo\Application\CasUser\Account\Table\Account;

use Chamilo\Application\CasUser\Account\Storage\DataClass\Account;
use Chamilo\Application\CasUser\Account\Manager;
use Chamilo\Libraries\Format\Structure\Toolbar;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Format\Table\Extension\DataClassTable\DataClassTableCellRenderer;
use Chamilo\Libraries\Format\Table\Interfaces\TableCellRendererActionsColumnSupport;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;

class AccountTableCellRenderer extends DataClassTableCellRenderer implements TableCellRendererActionsColumnSupport
{

    public function render_cell($column, $object)
    {
        switch ($column->get_name())
        {
            case Account :: PROPERTY_STATUS :
                return $object->get_status_icon();
        }
        return parent :: render_cell($column, $object);
    }

    public function get_actions($object)
    {
        $toolbar = new Toolbar();

        $toolbar->add_item(
            new ToolbarItem(
                Translation :: get('Edit', null, Utilities :: COMMON_LIBRARIES),
                Theme :: getInstance()->getCommonImagePath('action_edit'),
                $this->get_component()->get_url(
                    array(
                        Manager :: PARAM_ACTION => Manager :: ACTION_UPDATE,
                        Manager :: PARAM_ACCOUNT_ID => $object->get_id())),
                ToolbarItem :: DISPLAY_ICON));
        $toolbar->add_item(
            new ToolbarItem(
                Translation :: get('Delete', null, Utilities :: COMMON_LIBRARIES),
                Theme :: getInstance()->getCommonImagePath('action_delete'),
                $this->get_component()->get_url(
                    array(
                        Manager :: PARAM_ACTION => Manager :: ACTION_DELETE,
                        Manager :: PARAM_ACCOUNT_ID => $object->get_id())),
                ToolbarItem :: DISPLAY_ICON));

        if ($object->is_enabled())
        {
            $toolbar->add_item(
                new ToolbarItem(
                    Translation :: get('Deactivate', null, Utilities :: COMMON_LIBRARIES),
                    Theme :: getInstance()->getImagePath('Chamilo\Application\CasUser\Account', 'Action/Deactivate'),
                    $this->get_component()->get_url(
                        array(
                            Manager :: PARAM_ACTION => Manager :: ACTION_DEACTIVATE,
                            Manager :: PARAM_ACCOUNT_ID => $object->get_id())),
                    ToolbarItem :: DISPLAY_ICON));
        }
        else
        {
            $toolbar->add_item(
                new ToolbarItem(
                    Translation :: get('Activate', null, Utilities :: COMMON_LIBRARIES),
                    Theme :: getInstance()->getImagePath('Chamilo\Application\CasUser\Account', 'Action/Activate'),
                    $this->get_component()->get_url(
                        array(
                            Manager :: PARAM_ACTION => Manager :: ACTION_ACTIVATE,
                            Manager :: PARAM_ACCOUNT_ID => $object->get_id())),
                    ToolbarItem :: DISPLAY_ICON));
        }

        return $toolbar->as_html();
    }
}
