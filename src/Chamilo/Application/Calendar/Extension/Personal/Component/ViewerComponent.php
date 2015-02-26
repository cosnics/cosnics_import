<?php
namespace Chamilo\Application\Calendar\Extension\Personal\Component;

use Chamilo\Application\Calendar\Extension\Personal\Manager;
use Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\Publication;
use Chamilo\Application\Calendar\Extension\Personal\Storage\DataManager;
use Chamilo\Core\Group\Storage\DataClass\Group;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRendition;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRenditionImplementation;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\Format\Structure\ActionBarRenderer;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Utilities\DatetimeUtilities;
use Chamilo\Libraries\Utilities\Utilities;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;

/**
 *
 * @package application\calendar
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class ViewerComponent extends Manager implements DelegateComponent
{

    /**
     *
     * @var Publication
     */
    private $publication;

    /**
     *
     * @var \libraries\format\ActionBarRenderer
     */
    private $action_bar;

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $time = Request :: get(\Chamilo\Libraries\Calendar\Renderer\Renderer :: PARAM_TIME) ? intval(
            Request :: get(\Chamilo\Libraries\Calendar\Renderer\Renderer :: PARAM_TIME)) : time();
        $view = Request :: get(\Chamilo\Libraries\Calendar\Renderer\Renderer :: PARAM_TYPE) ? Request :: get(
            \Chamilo\Libraries\Calendar\Renderer\Renderer :: PARAM_TYPE) : \Chamilo\Libraries\Calendar\Renderer\Renderer :: TYPE_MONTH;

        $id = Request :: get(Manager :: PARAM_PUBLICATION_ID);

        if ($id)
        {
            $this->publication = DataManager :: retrieve_by_id(Publication :: class_name(), $id);

            if (! $this->can_view())
            {
                throw new NotAllowedException();
            }

            $output = $this->get_publication_as_html();

            $html = array();

            $html[] = $this->render_header();
            $html[] = $this->get_action_bar()->as_html() . '<br />';
            $html[] = '<div id="action_bar_browser">';
            $html[] = $output;
            $html[] = '</div>';
            $html[] = $this->render_footer();

            return implode("\n", $html);
        }
        else
        {
            return $this->display_error_page(htmlentities(Translation :: get('NoProfileSelected')));
        }
    }

    /**
     *
     * @return boolean
     */
    public function can_view()
    {
        $user = $this->get_user();

        $is_target = $this->publication->is_target($user);
        $is_publisher = ($this->publication->get_publisher() == $user->get_id());
        $is_platform_admin = $user->is_platform_admin();

        if (! $is_target && ! $is_publisher && ! $is_platform_admin)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     *
     * @return string
     */
    public function get_publication_as_html()
    {
        $content_object = $this->publication->get_publication_object();
        $content_object_properties = $content_object->get_properties();
        BreadcrumbTrail :: get_instance()->add(
            new Breadcrumb(null, $content_object_properties['default_properties']['title']));

        $html = array();

        $html[] = ContentObjectRenditionImplementation :: launch(
            $content_object,
            ContentObjectRendition :: FORMAT_HTML,
            ContentObjectRendition :: VIEW_FULL,
            $this);

        $html[] = $this->render_info();

        return implode("\n", $html);
    }

    public function render_info()
    {
        $action_bar = $this->get_action_bar();
        $html = array();

        $html[] = '<div class="event_publication_info">';
        $html[] = htmlentities(Translation :: get('PublishedOn', null, Utilities :: COMMON_LIBRARIES)) . ' ' .
             $this->render_publication_date();
        $html[] = htmlentities(Translation :: get('By', null, Utilities :: COMMON_LIBRARIES)) . ' ' .
             $this->publication->get_publication_publisher()->get_fullname();
        $html[] = htmlentities(Translation :: get('SharedWith', null, Utilities :: COMMON_LIBRARIES)) . ' ' .
             $this->render_publication_targets();
        $html[] = '</div>';

        return implode("\n", $html);
    }

    /**
     *
     * @return string
     */
    public function render_publication_date()
    {
        $date_format = Translation :: get('DateTimeFormatLong', null, Utilities :: COMMON_LIBRARIES);
        return DatetimeUtilities :: format_locale_date($date_format, $this->publication->get_published());
    }

    /**
     *
     * @return string
     */
    public function render_publication_targets()
    {
        if ($this->publication->is_for_nobody())
        {
            return htmlentities(Translation :: get('Nobody', null, \Chamilo\Core\User\Manager :: context()));
        }
        else
        {
            $users = $this->publication->get_target_users();
            $group_ids = $this->publication->get_target_groups();

            if (count($users) + count($group_ids) == 1)
            {
                if (count($users) == 1)
                {
                    $user = \Chamilo\Core\User\Storage\DataManager :: retrieve_by_id(
                        \Chamilo\Core\User\Storage\DataClass\User :: class_name(),
                        (int) $users[0]);
                    return $user->get_firstname() . ' ' . $user->get_lastname();
                }
                else
                {
                    $group = \Chamilo\Core\Group\Storage\DataManager :: retrieve_by_id(
                        Group :: class_name(),
                        $group_ids[0]);
                    return $group->get_name();
                }
            }

            $target_list = array();
            $target_list[] = '<select>';

            foreach ($users as $index => $user_id)
            {
                $user = \Chamilo\Core\User\Storage\DataManager :: retrieve_by_id(
                    \Chamilo\Core\User\Storage\DataClass\User :: class_name(),
                    (int) $users[0]);
                $target_list[] = '<option>' . $user->get_firstname() . ' ' . $user->get_lastname() . '</option>';
            }

            $condition = new InCondition(
                new PropertyConditionVariable(Group :: class_name(), Group :: PROPERTY_ID),
                $group_ids);
            $groups = \Chamilo\Core\Group\Storage\DataManager :: retrieves(
                Group :: class_name(),
                new DataClassRetrievesParameters($condition));

            while ($group = $groups->next_result())
            {
                $target_list[] = '<option>' . $group->get_name() . '</option>';
            }

            $target_list[] = '</select>';
            return implode("\n", $target_list);
        }
    }

    /**
     *
     * @return \libraries\format\ActionBarRenderer
     */
    public function get_action_bar()
    {
        if (! isset($this->action_bar))
        {
            $this->action_bar = new ActionBarRenderer(ActionBarRenderer :: TYPE_HORIZONTAL);

            $edit_url = $this->get_url(
                array(
                    self :: PARAM_ACTION => self :: ACTION_EDIT,
                    self :: PARAM_PUBLICATION_ID => $this->publication->get_id()));

            $delete_url = $this->get_url(
                array(
                    self :: PARAM_ACTION => self :: ACTION_DELETE,
                    self :: PARAM_PUBLICATION_ID => $this->publication->get_id()));

            $ical_url = $this->get_url(
                array(
                    self :: PARAM_ACTION => self :: ACTION_EXPORT,
                    self :: PARAM_PUBLICATION_ID => $this->publication->get_id()));

            $this->action_bar->add_tool_action(
                new ToolbarItem(
                    Translation :: get('ExportIcal'),
                    Theme :: getInstance()->getCommonImagePath() . 'export_csv.png',
                    $ical_url));

            $user = $this->get_user();

            if ($user->is_platform_admin() || $this->publication->get_publisher() == $user->get_id())
            {
                $this->action_bar->add_common_action(
                    new ToolbarItem(
                        Translation :: get('Edit', null, Utilities :: COMMON_LIBRARIES),
                        Theme :: getInstance()->getCommonImagePath() . 'action_edit.png',
                        $edit_url));
                $this->action_bar->add_common_action(
                    new ToolbarItem(
                        Translation :: get('Delete', null, Utilities :: COMMON_LIBRARIES),
                        Theme :: getInstance()->getCommonImagePath() . 'action_delete.png',
                        $delete_url));
            }
        }

        return $this->action_bar;
    }

    public function add_additional_breadcrumbs(BreadcrumbTrail $breadcrumbtrail)
    {
        $breadcrumbtrail->add_help('personal_calendar_viewer');
    }

    public function get_content_object_display_attachment_url($attachment)
    {
        return $this->get_url(
            array(
                Application :: PARAM_ACTION => Manager :: ACTION_VIEW_ATTACHMENT,
                self :: PARAM_PUBLICATION_ID => $this->publication->get_id(),
                self :: PARAM_OBJECT => $attachment->get_id()));
    }
}
