<?php
namespace Chamilo\Application\Weblcms\Renderer\ToolList\Type;

use Chamilo\Application\Weblcms\CourseSettingsConnector;
use Chamilo\Application\Weblcms\CourseSettingsController;
use Chamilo\Application\Weblcms\Manager;
use Chamilo\Application\Weblcms\Renderer\ToolList\ToolListRenderer;
use Chamilo\Application\Weblcms\Rights\WeblcmsRights;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Storage\DataClass\CourseSection;
use Chamilo\Application\Weblcms\Storage\DataClass\CourseSetting;
use Chamilo\Application\Weblcms\Storage\DataClass\CourseTool;
use Chamilo\Application\Weblcms\Storage\DataClass\CourseToolRelCourseSection;
use Chamilo\Application\Weblcms\Storage\DataManager;
use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Format\Tabs\DynamicContentTab;
use Chamilo\Libraries\Format\Tabs\DynamicTabsRenderer;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\OrderBy;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Utilities\StringUtilities;
use HTML_Table;

/**
 * $Id: fixed_location_tool_list_renderer.class.php 216 2009-11-13 14:08:06Z kariboe $
 *
 * @package application.lib.weblcms.tool_list_renderer
 */

/**
 * Tool list renderer which displays all course tools on a fixed location. Disabled tools will be shown in a disabled
 * looking way.
 */
class FixedLocationToolListRenderer extends ToolListRenderer
{

    /**
     * The available number of columns
     *
     * @var int
     */
    private $number_of_columns = 2;

    /**
     * Determine if the current user is a teacher
     *
     * @var boolean
     */
    private $is_course_admin;

    /**
     * The Course
     *
     * @var \application\weblcms\course\Course;
     */
    private $course;

    /**
     * Constructor
     *
     * @param WebLcms $parent The parent application
     */
    public function __construct($parent, $visible_tools)
    {
        parent :: __construct($parent, $visible_tools);

        $course = $parent->get_course();
        $this->course = $course;

        $course_settings_controller = CourseSettingsController :: get_instance();
        $course_tool_layout = $course_settings_controller->get_course_setting(
            $this->course,
            CourseSettingsConnector :: TOOL_LAYOUT);

        $this->number_of_columns = ($course_tool_layout % 2 == 0) ? 3 : 2;

        $this->is_course_admin = $this->get_parent()->get_parent()->is_teacher();
    }

    // Inherited
    public function toHtml()
    {
        $tools = array();
        $html = array();

        $course_settings_controller = CourseSettingsController :: get_instance();
        $course_tool_layout = $course_settings_controller->get_course_setting(
            $this->course,
            CourseSettingsConnector :: TOOL_LAYOUT);

        $visible_tools = $this->get_visible_tools();

        $section_types_map = array();

        $condition = new EqualityCondition(
            new PropertyConditionVariable(CourseSection :: class_name(), CourseSection :: PROPERTY_COURSE_ID),
            new StaticConditionVariable($this->course->get_id()));
        $order_property = array(
            new OrderBy(
                new PropertyConditionVariable(CourseSection :: class_name(), CourseSection :: PROPERTY_DISPLAY_ORDER)));
        $parameters = new DataClassRetrievesParameters($condition, null, null, $order_property);
        $sections = DataManager :: retrieves(CourseSection :: class_name(), $parameters);

        while ($section = $sections->next_result())
        {
            $section_types_map[$section->get_type()] = $section->get_id();
        }

        $sorted_tools = array();

        foreach ($visible_tools as $tool)
        {
            $tool_namespace = $tool->get_context();

            $sorted_tools[Translation :: get('TypeName', null, $tool_namespace)] = $tool;
        }

        ksort($sorted_tools);

        foreach ($sorted_tools as $tool)
        {
            if ($tool->get_name() == 'home')
            {
                continue;
            }

            $tools[$this->get_course_section_for_tool($tool, $section_types_map)][] = $tool;
        }

        $html[] = '<div id="coursecode" style="display: none;">' . $this->course->get_id() . '</div>';

        $tabs = new DynamicTabsRenderer('admin');

        $sections->reset();

        while ($section = $sections->next_result())
        {
            $sec_name = $section->get_type() == CourseSection :: TYPE_CUSTOM ? $section->get_name() : Translation :: get(
                $section->get_name());
            if (! $section->is_visible())
            {
                if (! $this->is_course_admin)
                {
                    continue;
                }
                else
                {
                    $name = '<span style="color: gray">' . $sec_name . '</span>';
                    $section->set_name($name);
                }
            }

            if ($section->get_type() == CourseSection :: TYPE_LINK)
            {
                if ($this->get_publication_links()->size() > 0)
                {
                    $content = $this->show_links($section);
                    $tabs->add_tab(new DynamicContentTab($section->get_id(), $sec_name, null, $content));
                }
            }
            else
            {
                if ($section->get_type() == CourseSection :: TYPE_DISABLED && ($course_tool_layout < 3 ||
                     ! $this->is_course_admin))
                {
                    continue;
                }

                if ($section->get_type() == CourseSection :: TYPE_ADMIN && ! $this->is_course_admin)
                {
                    continue;
                }

                if (($section->is_visible() && (count($tools[$section->get_id()]) > 0)) || $this->is_course_admin)
                {

                    $content = $this->display_block_header($section, $sec_name);
                    $content .= $this->show_section_tools($section, $tools[$section->get_id()]);
                    $content .= $this->display_block_footer($section);
                    $tabs->add_tab(new DynamicContentTab($section->get_id(), $sec_name, null, $content));
                }
            }
        }

        if (count($tabs->get_tabs()) > O)
        {
            $html[] = $tabs->render();
        }
        else
        {
            $html[] = '<div class="warning-message">' . Translation :: get('NoVisibleCourseSections') . '</div>';
        }

        $html[] = '<script type="text/javascript" src="' .
             Path :: getInstance()->getJavascriptPath('Chamilo\Libraries', true) . 'HomeAjax.js' . '"></script>';
        $html[] = '<script type="text/javascript" src="' .
             Path :: getInstance()->getJavascriptPath('Chamilo\Application\Weblcms', true) . 'CourseHome.js' .
             '"></script>';

        return implode(PHP_EOL, $html);
    }

    private function get_publication_links()
    {
        if (! isset($this->publication_links))
        {
            $parent = $this->get_parent();

            $conditions = array();
            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectPublication :: class_name(),
                    ContentObjectPublication :: PROPERTY_COURSE_ID),
                new StaticConditionVariable($parent->get_course_id()));
            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectPublication :: class_name(),
                    ContentObjectPublication :: PROPERTY_SHOW_ON_HOMEPAGE),
                new StaticConditionVariable(1));
            $condition = new AndCondition($conditions);

            $this->publication_links = DataManager :: retrieves(ContentObjectPublication :: class_name(), $condition);
        }

        return $this->publication_links;
    }

    /**
     * Returns the course section for a given tool object
     *
     * @param CourseTool $tool
     * @param int[int] $section_types_map
     *
     * @return int (the id of the course section)
     */
    protected function get_course_section_for_tool(CourseTool $tool, $section_types_map)
    {
        $course_settings_controller = CourseSettingsController :: get_instance();
        $course_tool_layout = $course_settings_controller->get_course_setting(
            $this->course,
            CourseSettingsConnector :: TOOL_LAYOUT);

        if ($course_tool_layout > 2)
        {
            $tool_visible = $course_settings_controller->get_course_setting(
                $this->course,
                CourseSetting :: COURSE_SETTING_TOOL_VISIBLE,
                $tool->get_id());

            if (! $tool_visible && $tool->get_section_type() != CourseSection :: TYPE_ADMIN)
            {
                return $section_types_map[CourseSection :: TYPE_DISABLED];
            }
        }

        if ($tool->get_section_type() != CourseSection :: TYPE_TOOL)
        {
            return $section_types_map[$tool->get_section_type()];
        }

        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                CourseToolRelCourseSection :: class_name(),
                CourseToolRelCourseSection :: PROPERTY_TOOL_ID),
            new StaticConditionVariable($tool->get_id()));
        $conditions[] = new InCondition(
            new PropertyConditionVariable(
                CourseToolRelCourseSection :: class_name(),
                CourseToolRelCourseSection :: PROPERTY_SECTION_ID),
            $section_types_map);

        $condition = new AndCondition($conditions);

        $course_tool_rel_course_section = DataManager :: retrieves(
            CourseToolRelCourseSection :: class_name(),
            $condition);

        if ($course_tool_rel_course_section->size() > 0)
        {
            return $course_tool_rel_course_section->next_result()->get_section_id();
        }

        return $section_types_map[CourseSection :: TYPE_TOOL];
    }

    /**
     * Show the links to publications in this course
     */
    private function show_links($section)
    {
        $parent = $this->get_parent();
        $publications = $this->get_publication_links();

        $table = new HTML_Table('style="width: 100%;"');
        $table->setColCount($this->number_of_columns);
        $count = 0;

        if ($publications->size() == 0)
        {
            $html[] = '<div class="normal-message">' . Translation :: get('NoLinksAvailable') . '</div>';
        }

        while ($publication = $publications->next_result())
        {
            if ($publication->is_hidden() == 0)
            {
                $lcms_action = \Chamilo\Application\Weblcms\Tool\Implementation\Home\Manager :: ACTION_HIDE_PUBLICATION;
                $visible_image = 'Action/Visible';
                $tool_image = Theme :: ICON_MEDIUM;
                $link_class = '';
            }
            else
            {
                $lcms_action = \Chamilo\Application\Weblcms\Tool\Implementation\Home\Manager :: ACTION_SHOW_PUBLICATION;
                $visible_image = 'Action/Invisible';
                $tool_image = Theme :: ICON_MEDIUM . 'Na';
                $link_class = ' class="invisible"';
            }

            $title = htmlspecialchars($publication->get_content_object()->get_title());
            $row = $count / $this->number_of_columns;
            $col = $count % $this->number_of_columns;
            $cell_contents = array();
            if ($parent->is_allowed(WeblcmsRights :: EDIT_RIGHT) || $publication->is_visible_for_target_users())
            {
                // Show visibility-icon
                if ($parent->is_allowed(WeblcmsRights :: EDIT_RIGHT))
                {
                    $cell_contents[] = '<a href="' .
                         $parent->get_url(
                            array(
                                \Chamilo\Application\Weblcms\Tool\Manager :: PARAM_ACTION => $lcms_action,
                                \Chamilo\Application\Weblcms\Tool\Manager :: PARAM_PUBLICATION_ID => $publication->get_id())) .
                         '"><img src="' . Theme :: getInstance()->getCommonImagePath($visible_image) .
                         '" style="vertical-align: middle;" alt=""/></a>';
                }

                // Show delete-icon
                if ($parent->is_allowed(WeblcmsRights :: DELETE_RIGHT))
                {
                    $cell_contents[] = '<a href="' .
                         $parent->get_url(
                            array(
                                \Chamilo\Application\Weblcms\Tool\Manager :: PARAM_ACTION => \Chamilo\Application\Weblcms\Tool\Implementation\Home\Manager :: ACTION_DELETE_LINKS,
                                \Chamilo\Application\Weblcms\Tool\Manager :: PARAM_PUBLICATION_ID => $publication->get_id())) .
                         '"><img src="' . Theme :: getInstance()->getCommonImagePath('Action/Delete') .
                         '" style="vertical-align: middle;" alt=""/></a>';
                }
                $cell_contents[] = '&nbsp;&nbsp;&nbsp;';
                // Show tool-icon + name

                if ($publication->get_tool() ==
                     \Chamilo\Application\Weblcms\Tool\Implementation\Link\Manager :: TOOL_NAME)
                {
                    $url = $publication->get_content_object()->get_url();
                    $target = ' target="_blank"';
                }
                else
                {
                    $class = 'Chamilo\Application\Weblcms\Tool\Implementation\\' .
                         StringUtilities :: getInstance()->createString($publication->get_tool())->upperCamelize() .
                         '\Manager';
                    $url = $parent->get_url(
                        array(
                            'tool_action' => null,
                            Manager :: PARAM_COMPONENT_ACTION => null,
                            Manager :: PARAM_TOOL => $publication->get_tool(),
                            \Chamilo\Application\Weblcms\Tool\Manager :: PARAM_ACTION => $class :: ACTION_VIEW,
                            \Chamilo\Application\Weblcms\Tool\Manager :: PARAM_PUBLICATION_ID => $publication->get_id()),
                        array(),
                        true);
                    $target = '';
                }

                $cell_contents[] = '<a href="' . $url . '"' . $target . $link_class . '>';
                $cell_contents[] = '<img src="' . Theme :: getInstance()->getImagePath(
                    \Chamilo\Application\Weblcms\Tool\Manager :: get_tool_type_namespace($publication->get_tool()),
                    'Logo/' . $tool_image) . '" style="vertical-align: middle;" alt="' . $title . '" width="' .
                     Theme :: ICON_MEDIUM . '" height="' . Theme :: ICON_MEDIUM . '"/>';
                $cell_contents[] = '&nbsp;';
                $cell_contents[] = $title;
                $cell_contents[] = '</a>';

                $table->setCellContents($row, $col, implode(PHP_EOL, $cell_contents));
                $table->updateColAttributes($col, 'style="width: ' . floor(100 / $this->number_of_columns) . '%;"');
                $count ++;
            }
        }

        $html[] = $table->toHtml();

        return implode(PHP_EOL, $html);
    }

    public function display_block_header($section, $block_name)
    {
        $html = array();

        if ($section->get_type() == CourseSection :: TYPE_TOOL)
        {
            $html[] = '<div class="toolblock" id="block_' . $section->get_id() . '" style="width:100%;">';
        }

        if ($section->get_type() == CourseSection :: TYPE_DISABLED)
        {
            $html[] = '<div class="disabledblock" id="block_' . $section->get_id() . '" style="width:100%;">';
        }

        return implode(PHP_EOL, $html);
    }

    public function display_block_footer($section)
    {
        $html = array();

        $html[] = '<div class="clear"></div>';

        if ($section->get_type() == CourseSection :: TYPE_TOOL || $section->get_type() == CourseSection :: TYPE_DISABLED)
        {
            $html[] = '</div>';
        }

        return implode(PHP_EOL, $html);
    }

    private function show_section_tools($section, $tools)
    {
        $parent = $this->get_parent();

        $column_width = 99.9 / $this->number_of_columns;

        $count = 0;

        $html = array();

        if (count($tools) == 0)
        {
            $html[] = '<div class="normal-message">' . Translation :: get('NoToolsAvailable') . '</div>';
        }

        $course_settings_controller = CourseSettingsController :: get_instance();

        foreach ($tools as $tool)
        {
            $tool_namespace = $tool->get_context();

            $tool_visible = $course_settings_controller->get_course_setting(
                $this->course,
                CourseSetting :: COURSE_SETTING_TOOL_VISIBLE,
                $tool->get_id());

            if ($tool_visible || $section->get_type() == CourseSection :: TYPE_ADMIN)
            {
                $lcms_action = \Chamilo\Application\Weblcms\Tool\Implementation\Home\Manager :: ACTION_MAKE_TOOL_INVISIBLE;
                $visible_image = 'Action/Visible';
                $new = '';
                if ($parent->tool_has_new_publications($tool->get_name(), $this->course))
                {
                    $new = 'New';
                }
                $tool_image = Theme :: ICON_MEDIUM . $new;
                $link_class = '';
            }
            else
            {
                $lcms_action = \Chamilo\Application\Weblcms\Tool\Implementation\Home\Manager :: ACTION_MAKE_TOOL_VISIBLE;
                $visible_image = 'Action/Invisible';
                $tool_image = Theme :: ICON_MEDIUM . 'Na';
                $link_class = ' class="invisible"';
            }

            $title = Translation :: get('TypeName', null, $tool_namespace);

            // $row = $count / $this->number_of_columns;
            // $col = $count % $this->number_of_columns;

            if ($section->get_type() == CourseSection :: TYPE_TOOL ||
                 $section->get_type() == CourseSection :: TYPE_DISABLED)
            {
                $html[] = '<div id="tool_' . $tool->get_id() . '" class="tool" style="width: ' . $column_width . '%;">';
                $id = ' id="drag_' . $tool->get_id() . '"';
            }
            else
            {
                $html[] = '<div class="tool" style="width: ' . $column_width . '%;">';
            }

            // Show visibility-icon
            if ($this->is_course_admin && $section->get_type() != CourseSection :: TYPE_ADMIN)
            {
                $html[] = '<a href="' .
                     $parent->get_url(
                        array(
                            \Chamilo\Application\Weblcms\Tool\Implementation\Home\Manager :: PARAM_ACTION => $lcms_action,
                            \Chamilo\Application\Weblcms\Tool\Implementation\Home\Manager :: PARAM_TOOL => $tool->get_name())) .
                     '"><img class="tool_visible" src="' . Theme :: getInstance()->getCommonImagePath($visible_image) .
                     '" style="vertical-align: middle;" alt="" /></a>';
                $html[] = '&nbsp;&nbsp;&nbsp;';
            }

            // Show tool-icon + name
            $html[] = '<img class="tool_image"' . $id . ' src="' . Theme :: getInstance()->getImagePath(
                $tool_namespace,
                'Logo/' . $tool_image) . '" style="vertical-align: middle;" alt="' . $title . '" width="' .
                 Theme :: ICON_MEDIUM . '" height="' . Theme :: ICON_MEDIUM . '"/>';
            $html[] = '&nbsp;';
            $html[] = '<a id="tool_text" href="' . $parent->get_url(
                array(Manager :: PARAM_TOOL => $tool->get_name()),
                array(
                    Manager :: PARAM_COMPONENT_ACTION,
                    \Chamilo\Application\Weblcms\Tool\Manager :: PARAM_ACTION,
                    \Chamilo\Application\Weblcms\Tool\Manager :: PARAM_BROWSER_TYPE,
                    Manager :: PARAM_CATEGORY),
                true) . '" ' . $link_class . '>';
            $html[] = $title;
            $html[] = '</a>';

            $html[] = '<div class="clear"></div>';

            $html[] = '</div>';

            $count ++;
        }

        $html[] = ' ';

        return implode(PHP_EOL, $html);
    }
}
