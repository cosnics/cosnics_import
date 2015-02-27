<?php
namespace Chamilo\Core\Rights\Editor\Component;

use Chamilo\Core\Rights\Editor\Form\SimpleRightsEditorForm;
use Chamilo\Libraries\Format\Structure\ActionBarRenderer;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Translation;

/**
 * Simple interface to edit rights
 *
 * @author Sven Vanpoucke
 * @package application.common.rights_editor_manager.component
 */
class SimpleRightsEditorComponent extends RightsEditorComponent
{

    public function run()
    {
        $this->action_bar = $this->get_action_bar();
        $form = new SimpleRightsEditorForm(
            $this->get_url(),
            $this->get_context(),
            $this->get_locations(),
            $this->get_available_rights(),
            $this->get_entities());

        if ($form->validate())
        {
            $succes = $form->handle_form_submit();

            $message = Translation :: get($succes ? 'RightsChanged' : 'RightsNotChanged');
            $this->redirect($message, ! $succes);
        }

        $html = array();

        $html[] = $this->render_header();
        $html[] = '<br />';
        $html[] = $this->action_bar->as_html();
        $html[] = '<div id="action_bar_browser" style="width:100%;">';
        $html[] = $form->toHtml();
        $html[] = '</div>';
        $html[] = $this->render_footer();

        return implode(PHP_EOL, $html);
    }

    /**
     * Builds the actionbar;
     *
     * @return ActionBarRenderer
     */
    public function get_action_bar()
    {
        $action_bar = new ActionBarRenderer(ActionBarRenderer :: TYPE_HORIZONTAL);

        $action_bar->add_common_action(
            new ToolbarItem(
                Translation :: get('AdvancedRightsEditor'),
                Theme :: getInstance()->getCommonImagesPath() . 'action_config.png',
                $this->get_url(array(self :: PARAM_ACTION => self :: ACTION_EDIT_ADVANCED_RIGHTS)),
                ToolbarItem :: DISPLAY_ICON_AND_LABEL));

        return $action_bar;
    }
}
