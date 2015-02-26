<?php
namespace Chamilo\Configuration\Category\Component;

use Chamilo\Configuration\Category\Form\CategoryForm;
use Chamilo\Configuration\Category\Manager;
use Chamilo\Configuration\Category\Menu\CategoryMenu;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;

/**
 * $Id: creator.class.php 205 2009-11-13 12:57:33Z vanpouckesven $
 *
 * @package application.common.category_manager.component
 */
class CreatorComponent extends Manager
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $this->set_parameter(self :: PARAM_CATEGORY_ID, Request :: get(self :: PARAM_CATEGORY_ID));
        $trail = BreadcrumbTrail :: get_instance();
        $trail->add_help('category_manager_creator');
        $trail->add(new Breadcrumb($this->get_url(), Translation :: get('CreatorComponent')));

        if (Request :: get(self :: PARAM_CATEGORY_ID))
        {
            $menu = new CategoryMenu(Request :: get(self :: PARAM_CATEGORY_ID), $this->get_parent());
        }

        $category_id = Request :: get(self :: PARAM_CATEGORY_ID);
        $user = $this->get_user();

        $category = $this->get_parent()->get_category();
        $category->set_parent(isset($category_id) ? $category_id : 0);

        $form = new CategoryForm(
            CategoryForm :: TYPE_CREATE,
            $this->get_url(array(self :: PARAM_CATEGORY_ID => $category_id)),
            $category,
            $user,
            $this);

        if ($form->validate())
        {
            $success = $form->create_category();
            $this->redirect(
                Translation :: get($success ? 'CategoryCreated' : 'CategoryNotCreated'),
                ($success ? false : true),
                array(
                    self :: PARAM_ACTION => self :: ACTION_BROWSE_CATEGORIES,
                    self :: PARAM_CATEGORY_ID => Request :: get(self :: PARAM_CATEGORY_ID)));
        }
        else
        {
            $html = array();

            $html[] = $this->render_header();
            $html[] = '<br />';
            $html[] = $form->toHtml();
            $html[] = $this->render_footer();

            return implode("\n", $html);
        }
    }
}
