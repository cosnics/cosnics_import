<?php
namespace Chamilo\Core\Group\Component;

use Chamilo\Core\Group\Integration\Chamilo\Core\Tracking\Storage\DataClass\Change;
use Chamilo\Core\Group\Manager;
use Chamilo\Core\Tracking\Storage\DataClass\Event;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Format\Tabs\TabsRenderer;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 *
 * @package group.lib.group_manager.component
 */
class DeleterComponent extends Manager
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $ids = $this->getRequest()->getFromPostOrUrl(self::PARAM_GROUP_ID);

        $this->set_parameter(self::PARAM_GROUP_ID, $ids);

        $user = $this->get_user();

        if (! $this->get_user()->is_platform_admin())
        {
            throw new NotAllowedException();
        }

        $trail = BreadcrumbTrail::getInstance();

        $redirect = new Redirect(
            array(
                Application::PARAM_CONTEXT => \Chamilo\Core\Admin\Manager::context(),
                \Chamilo\Core\Admin\Manager::PARAM_ACTION => \Chamilo\Core\Admin\Manager::ACTION_ADMIN_BROWSER));
        $trail->add(new Breadcrumb($redirect->getUrl(), Translation::get('Administration')));

        $redirect = new Redirect(
            array(
                Application::PARAM_CONTEXT => \Chamilo\Core\Admin\Manager::context(),
                \Chamilo\Core\Admin\Manager::PARAM_ACTION => \Chamilo\Core\Admin\Manager::ACTION_ADMIN_BROWSER,
                TabsRenderer::PARAM_SELECTED_TAB => ClassnameUtilities::getInstance()->getNamespaceId(
                    self::package())));
        $trail->add(new Breadcrumb($redirect->getUrl(), Translation::get('Group')));

        $trail->add(
            new Breadcrumb(
                $this->get_url(array(Application::PARAM_ACTION => self::ACTION_BROWSE_GROUPS)),
                Translation::get('GroupList')));

        $trail->add(new Breadcrumb($this->get_url(), Translation::get('DeleteGroup')));
        $trail->add_help('group general');

        $failures = 0;

        if (! empty($ids))
        {
            if (! is_array($ids))
            {
                $ids = array($ids);
            }

            foreach ($ids as $id)
            {
                $group = $this->retrieve_group($id);

                if (! $group->delete())
                {
                    $failures ++;
                }
                else
                {
                    Event::trigger(
                        'Delete',
                        Manager::context(),
                        array(
                            Change::PROPERTY_REFERENCE_ID => $group->get_id(),
                            Change::PROPERTY_USER_ID => $user->get_id()));
                }
            }

            if ($failures)
            {
                if (count($ids) == 1)
                {
                    $message = Translation::get(
                        'ObjectNotDeleted',
                        array('OBJECT' => Translation::get('SelectedGroup')),
                        StringUtilities::LIBRARIES);
                }
                else
                {
                    $message = Translation::get(
                        'ObjectsNotDeleted',
                        array('OBJECT' => Translation::get('SelectedGroups')),
                        StringUtilities::LIBRARIES);
                }
            }
            else
            {
                if (count($ids) == 1)
                {
                    $message = Translation::get(
                        'ObjectDeleted',
                        array('OBJECT' => Translation::get('SelectedGroup')),
                        StringUtilities::LIBRARIES);
                }
                else
                {
                    $message = Translation::get(
                        'ObjectsDeleted',
                        array('OBJECT' => Translation::get('SelectedGroups')),
                        StringUtilities::LIBRARIES);
                }
            }

            $this->redirect(
                $message, (bool) $failures,
                array(Application::PARAM_ACTION => self::ACTION_BROWSE_GROUPS),
                array(self::PARAM_GROUP_ID));
        }
        else
        {
            return $this->display_error_page(
                htmlentities(Translation::get('NoObjectsSelected', null, StringUtilities::LIBRARIES)));
        }
    }

    public function add_additional_breadcrumbs(BreadcrumbTrail $breadcrumbtrail)
    {
        $breadcrumbtrail->add(
            new Breadcrumb(
                $this->get_url(array(Application::PARAM_ACTION => self::ACTION_BROWSE_GROUPS)),
                Translation::get('BrowserComponent')));
        $breadcrumbtrail->add(
            new Breadcrumb(
                $this->get_url(
                    array(
                        Application::PARAM_ACTION => self::ACTION_VIEW_GROUP,
                        self::PARAM_GROUP_ID => Request::get(self::PARAM_GROUP_ID))),
                Translation::get('ViewerComponent')));
        $breadcrumbtrail->add_help('group general');
    }
}
