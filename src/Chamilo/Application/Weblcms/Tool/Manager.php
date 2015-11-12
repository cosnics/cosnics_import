<?php
namespace Chamilo\Application\Weblcms\Tool;

use Chamilo\Application\Weblcms\CourseSettingsConnector;
use Chamilo\Application\Weblcms\CourseSettingsController;
use Chamilo\Application\Weblcms\Renderer\PublicationList\ContentObjectPublicationListRenderer;
use Chamilo\Application\Weblcms\Renderer\ToolList\ToolListRenderer;
use Chamilo\Application\Weblcms\Rights\Entities\CourseGroupEntity;
use Chamilo\Application\Weblcms\Rights\Entities\CoursePlatformGroupEntity;
use Chamilo\Application\Weblcms\Rights\Entities\CourseUserEntity;
use Chamilo\Application\Weblcms\Rights\WeblcmsRights;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublicationCategory;
use Chamilo\Application\Weblcms\Storage\DataClass\CourseSetting;
use Chamilo\Application\Weblcms\Storage\DataClass\CourseTool;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRendition;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRenditionImplementation;
use Chamilo\Core\Repository\ContentObject\Introduction\Storage\DataClass\Introduction;
use Chamilo\Core\Rights\RightsUtil;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Application\ApplicationFactory;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Architecture\Exceptions\ObjectNotExistException;
use Chamilo\Libraries\File\Filesystem;
use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Format\Structure\Toolbar;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Session\Session;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Utilities\StringUtilities;
use Chamilo\Libraries\Utilities\Utilities;
use Exception;
use Chamilo\Libraries\Architecture\Application\ApplicationConfigurationInterface;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;

/**
 * This is the base class for all tools used in applications.
 *
 * @author Tim De Pauw
 * @author Hans De Bisschop - Erasmus Hogeschool Brussel - Refactoring
 * @author Sven Vanpoucke - Hogeschool Gent - Refactoring, Cleanup
 */
abstract class Manager extends Application
{
    /**
     * **************************************************************************************************************
     * URL Parameters *
     * **************************************************************************************************************
     */
    const PARAM_ACTION = 'tool_action';
    const PARAM_PUBLICATION_ID = 'publication';
    const PARAM_COMPLEX_ID = 'cid';
    const PARAM_MOVE = 'move';
    const PARAM_VISIBILITY = 'visible';
    const PARAM_OBJECT_ID = 'object_id';
    const PARAM_BROWSER_TYPE = 'browser';
    const PARAM_TEMPLATE_NAME = 'template_name';
    const PARAM_PUBLISH_MODE = 'publish_mode';
    const PARAM_MOVE_DIRECTION = 'move_direction';
    const PARAM_BROWSE_PUBLICATION_TYPE = 'pub_type';
    const PARAM_VIEW_AS_ID = 'view_as_id';
    const PARAM_VIEW_AS_COURSE_ID = 'view_as_course_id';

    /**
     * **************************************************************************************************************
     * Actions *
     * **************************************************************************************************************
     */
    const ACTION_BROWSE = 'Browser';
    const ACTION_VIEW = 'Viewer';
    const ACTION_PUBLISH = 'Publisher';
    const ACTION_DELETE = 'Deleter';
    const ACTION_UPDATE = 'Updater';
    const ACTION_TOGGLE_VISIBILITY = 'ToggleVisibility';
    const ACTION_MOVE = 'Mover';
    const ACTION_MOVE_TO_CATEGORY = 'CategoryMover';
    const ACTION_PUBLISH_INTRODUCTION = 'IntroductionPublisher';
    const ACTION_MANAGE_CATEGORIES = 'CategoryManager';
    const ACTION_VIEW_REPORTING_TEMPLATE = 'ReportingViewer';
    const ACTION_BUILD_COMPLEX_CONTENT_OBJECT = 'ComplexBuilder';
    const ACTION_DISPLAY_COMPLEX_CONTENT_OBJECT = 'ComplexDisplay';
    const ACTION_SHOW_PUBLICATION = 'ShowPublication';
    const ACTION_HIDE_PUBLICATION = 'HidePublication';
    const ACTION_EVALUATE_TOOL_PUBLICATION = 'Evaluate';
    const ACTION_EDIT_RIGHTS = 'RightsEditor';
    const ACTION_UPDATE_CONTENT_OBJECT = 'ContentObjectUpdater';
    const ACTION_UPDATE_PUBLICATION = 'PublicationUpdater';
    const ACTION_VIEW_ATTACHMENT = 'AttachmentViewer';
    const ACTION_CREATE_BOOKMARK = 'CourseBookmarkCreator';
    const ACTION_MAIL_PUBLICATION = 'PublicationMailer';
    const DEFAULT_ACTION = self :: ACTION_BROWSE;

    /**
     * **************************************************************************************************************
     * Publication Types *
     * **************************************************************************************************************
     */
    const PUBLICATION_TYPE_ALL = 1;
    const PUBLICATION_TYPE_FOR_ME = 2;
    const PUBLICATION_TYPE_FROM_ME = 3;
    const PUBLISH_TYPE_FORM = 1;
    const PUBLISH_TYPE_AUTO = 2;
    const PUBLISH_TYPE_BOTH = 3;
    const PUBLISH_MODE_QUICK = 1;
    const PUBLISH_MODE_FULL = 2;
    /**
     * **************************************************************************************************************
     * Publish dependent on tool type
     * **************************************************************************************************************
     */
    const PUBLISH_INDEPENDENT = 0;
    const PUBLISH_DEPENDENT = 1;

    /**
     * **************************************************************************************************************
     * Move Directions *
     * **************************************************************************************************************
     */
    const PARAM_MOVE_DIRECTION_UP = - 1;
    const PARAM_MOVE_DIRECTION_DOWN = 1;

    /**
     * **************************************************************************************************************
     * Construct Functionality *
     * **************************************************************************************************************
     */

    /**
     * Constructor.
     *
     * @param $parent Application - The component in which this tool runs
     */
    public function __construct(ApplicationConfigurationInterface $applicationConfiguration)
    {
        parent :: __construct($applicationConfiguration);

        if ($this->get_course_id() && ! $this->get_parent()->is_tool_accessible())
        {
            throw new NotAllowedException();
        }
        $this->set_parameter(
            \Chamilo\Application\Weblcms\Manager :: PARAM_CATEGORY,
            Request :: get(\Chamilo\Application\Weblcms\Manager :: PARAM_CATEGORY));
        $this->set_parameter(self :: PARAM_BROWSER_TYPE, $this->get_browser_type());
    }

    /**
     * Launches the tool with a given type
     *
     * @param $type string
     * @param $application Application
     */
    public static function factory_and_launch($namespace, $application)
    {
        $class = $namespace . '\Manager';

        if (! class_exists($class))
        {
            throw new Exception(Translation :: get('ToolTypeDoesNotExist', array('type' => $namespace)));
        }

        $factory = new ApplicationFactory(
            $namespace,
            new ApplicationConfiguration($application->getRequest(), $application->get_user(), $application));
        return $factory->run();
    }

    /**
     * **************************************************************************************************************
     * Main Functionality *
     * **************************************************************************************************************
     */
    public function get_browser_type()
    {
        $browser_type = Request :: get(self :: PARAM_BROWSER_TYPE);

        if ($browser_type && in_array($browser_type, $this->get_available_browser_types()))
        {
            return $browser_type;
        }
        else
        {
            $available_browser_types = $this->get_available_browser_types();
            return $available_browser_types[0];
        }
    }

    public function get_available_browser_types()
    {
        $browser_types = array();
        $browser_types[] = ContentObjectPublicationListRenderer :: TYPE_LIST;
        $browser_types[] = ContentObjectPublicationListRenderer :: TYPE_TABLE;
        return $browser_types;
    }

    /**
     * Returns the name of the tool
     *
     * @return String
     */
    public function get_tool_id()
    {
        return $this->get_tool_registration()->get_name();
    }

    /**
     * Returns the registration object of the tool
     *
     * @return CourseTool
     */
    public function get_tool_registration()
    {
        return $this->get_parent()->get_tool_registration();
    }

    public function render_header($visible_tools = null, $show_introduction_text = false)
    {
        if (! $visible_tools)
        {
            $visible_tools = $this->get_visible_tools();
        }

        $html = array();

        $html[] = parent :: render_header();
        $html[] = $this->display_course_menus($visible_tools, $show_introduction_text);

        return implode(PHP_EOL, $html);
    }

    public function render_footer()
    {
        $html = array();

        $html[] = '</div>';
        $html[] = parent :: render_footer();

        return implode(PHP_EOL, $html);
    }

    /**
     * Returns the visible tools for this course
     *
     * @return CourseTool[]
     */
    public function get_visible_tools()
    {
        $tools = array();

        $course_tools = \Chamilo\Application\Weblcms\Storage\DataManager :: retrieves(
            CourseTool :: class_name(),
            new DataClassRetrievesParameters());

        $edit_right = $this->is_allowed(WeblcmsRights :: EDIT_RIGHT);

        $course_settings_controller = CourseSettingsController :: get_instance();

        while ($tool = $course_tools->next_result())
        {
            $tool_active = $course_settings_controller->get_course_setting(
                $this->get_course(),
                CourseSetting :: COURSE_SETTING_TOOL_ACTIVE,
                $tool->get_id());

            $tool_visible = $course_settings_controller->get_course_setting(
                $this->get_course(),
                CourseSetting :: COURSE_SETTING_TOOL_VISIBLE,
                $tool->get_id());

            if ($tool_active && ($edit_right || $tool_visible))
            {
                $tools[] = $tool;
            }
        }

        return $tools;
    }

    public function display_course_menus($tools, $show_introduction_text = false)
    {
        $html = array();

        $shortcuts_visible = false;

        $course_settings_controller = CourseSettingsController :: get_instance();

        $menu_layout = $course_settings_controller->get_course_setting(
            $this->get_course(),
            CourseSettingsConnector :: MENU_LAYOUT);

        if ($menu_layout != CourseSettingsConnector :: MENU_LAYOUT_OFF && count($tools) > 0)
        {
            $renderer = ToolListRenderer :: factory(ToolListRenderer :: TYPE_MENU, $this, $tools);

            $html[] = $renderer->toHtml();
            $html[] = '<div id="tool_browser_' .
                 ($renderer->display_menu_icons() && ! $renderer->display_menu_text() ? 'icon_' : '') .
                 $renderer->get_menu_style() . '">';
        }
        else
        {
            $html[] = '<div id="tool_browser">';
        }

        $tool_shortcut = $course_settings_controller->get_course_setting(
            $this->get_course(),
            CourseSettingsConnector :: TOOL_SHORTCUT_MENU);

        $introduction_text_allowed = $course_settings_controller->get_course_setting(
            $this->get_course(),
            CourseSettingsConnector :: ALLOW_INTRODUCTION_TEXT);

        if (($this->get_tool_id() == 'home' && $introduction_text_allowed && ! $this->get_introduction_text()) ||
             ($tool_shortcut && count($tools) > 0))
        {
            $html[] = '<div style="margin-bottom: 10px;">';
            $shortcuts_visible = true;
        }

        $toolbar = new Toolbar();

        $is_subscribed = \Chamilo\Application\Weblcms\Course\Storage\DataManager :: is_subscribed(
            $this->get_course(),
            $this->get_user());
        if (! $is_subscribed)
        {
            $params = array();
            $params[Application :: PARAM_ACTION] = Manager :: ACTION_CREATE_BOOKMARK;

            $bookmark_url = $this->get_parent()->get_url($params);

            $onclick = '" onclick="javascript:openPopup(\'' . $bookmark_url . '\'); return false;';
            $toolbar->add_item(
                new ToolbarItem(
                    Translation :: get('MakeBookmark'),
                    null,
                    $bookmark_url,
                    ToolbarItem :: DISPLAY_ICON_AND_LABEL,
                    false,
                    $onclick,
                    '_blank'));
        }

        if ($show_introduction_text)
        {
            $introduction_text = $this->get_introduction_text();
            if (! $introduction_text)
            {
                if ($this->is_allowed(WeblcmsRights :: EDIT_RIGHT))
                {
                    $toolbar->add_item(
                        new ToolbarItem(
                            Translation :: get('PublishIntroductionText'),
                            Theme :: getInstance()->getCommonImagePath('Action/Introduce'),
                            $this->get_url(array(self :: PARAM_ACTION => self :: ACTION_PUBLISH_INTRODUCTION))));
                }
            }
        }

        if ($toolbar->has_items())
        {
            $html[] = '<div class="home_actions">';
            $html[] = $toolbar->as_html();
            $html[] = '</div>';
        }

        if ($tool_shortcut && count($tools) > 0)
        {
            $renderer = ToolListRenderer :: factory(ToolListRenderer :: TYPE_SHORTCUT, $this, $tools);
            $html[] = '<div style="float:right;">';
            $html[] = $renderer->toHtml();
            $html[] = '</div>';
        }

        if ($shortcuts_visible)
        {
            $html[] = '<div class="clear"></div>';
            $html[] = '</div>';
        }

        $html[] = '<div class="clear"></div>';

        return implode(PHP_EOL, $html);
    }

    public function get_user_info($user_id)
    {
        return $this->get_parent()->get_user_info($user_id);
    }

    public function get_course()
    {
        return $this->get_parent()->get_course();
    }

    public function get_course_id()
    {
        return $this->get_parent()->get_course_id();
    }

    public function get_course_groups()
    {
        return $this->get_parent()->get_course_groups();
    }

    public function get_course_group()
    {
        return $this->get_parent()->get_course_group();
    }

    /**
     * Check if the current user has a given right in this tool
     *
     * @param $right int
     * @param $publication ContentObjectPublication
     * @return boolean True if the current user has the right
     */
    public function is_allowed($right, $publication = null)
    {
        $studentview = Session :: retrieve('studentview');
        if ($studentview == 1)
        {
            return false;
        }
        // add check for student view/login as
        $id = Session :: get_user_id();
        $va_id = Session :: get(self :: PARAM_VIEW_AS_ID);
        $course_id = Session :: get(self :: PARAM_VIEW_AS_COURSE_ID);
        // fake the id with the set "login as id" only if we're in the right
        // course
        if (isset($va_id) && isset($course_id))
        {
            if ($course_id == $this->get_course_id())
            {
                $id = $va_id;
            }
            else
            {
                // not the course we're viewing as another user, so check
                // regular stuff
                if ($this->get_parent()->is_teacher())
                {
                    return true;
                }
            }
        }
        else // try to get rid of duplication
        {
            // no valid view as information
            if ($this->get_parent()->is_teacher())
            {
                return true;
            }
        }
        if ($publication)
        {
            if ($publication instanceof ContentObjectPublication)
            {
                $category_id = $publication->get_category_id();
                $publication_id = $publication->get_id();
                $publisher_id = $publication->get_publisher_id();
                $hidden = $publication->is_hidden();
            }
            else
            {
                $category_id = $publication[ContentObjectPublication :: PROPERTY_CATEGORY_ID];
                $publication_id = $publication[ContentObjectPublication :: PROPERTY_ID];
                $publisher_id = $publication[ContentObjectPublication :: PROPERTY_PUBLISHER_ID];
                $hidden = $publication[ContentObjectPublication :: PROPERTY_HIDDEN];
            }

            if ($category_id != 0)
            {
                $category = \Chamilo\Application\Weblcms\Storage\DataManager :: retrieve_by_id(
                    ContentObjectPublicationCategory :: class_name(),
                    $category_id);

                if (! $category->is_recursive_visible())
                {
                    return false;
                }
            }

            if ($publisher_id == $id)
            {
                return true; // the publisher has all the rights
            }

            if ($hidden && $right == WeblcmsRights :: VIEW_RIGHT)
            {
                return WeblcmsRights :: get_instance()->is_allowed_in_courses_subtree(
                    WeblcmsRights :: EDIT_RIGHT,
                    $publication_id,
                    WeblcmsRights :: TYPE_PUBLICATION,
                    $this->get_course_id(),
                    $id);
            }
            return WeblcmsRights :: get_instance()->is_allowed_in_courses_subtree(
                $right,
                $publication_id,
                WeblcmsRights :: TYPE_PUBLICATION,
                $this->get_course_id(),
                $id);
        }
        else
        {
            $category_id = Request :: get(\Chamilo\Application\Weblcms\Manager :: PARAM_CATEGORY);

            if ($category_id)
            {
                $category = \Chamilo\Application\Weblcms\Storage\DataManager :: retrieve_by_id(
                    ContentObjectPublicationCategory :: class_name(),
                    $category_id);
                if (! isset($category))
                    throw new ObjectNotExistException(
                        Translation :: get('ContentObjectPublicationCategory'),
                        $category_id);

                if ($category->is_recursive_visible())
                {
                    return WeblcmsRights :: get_instance()->is_allowed_in_courses_subtree(
                        $right,
                        $category_id,
                        WeblcmsRights :: TYPE_COURSE_CATEGORY,
                        $this->get_course_id(),
                        $id);
                }
                else
                {
                    return false;
                }
            }

            if ($this->get_tool_id() == 'home')
            {
                return WeblcmsRights :: get_instance()->is_allowed_in_courses_subtree(
                    $right,
                    0,
                    RightsUtil :: TYPE_ROOT,
                    $this->get_course_id(),
                    $id);
            }

            $tool_registration = $this->get_tool_registration();
            $course_settings_controller = CourseSettingsController :: get_instance();

            $module_visible = $course_settings_controller->get_course_setting(
                $this->get_course(),
                CourseSetting :: COURSE_SETTING_TOOL_VISIBLE,
                $tool_registration->get_id());

            if (! $module_visible)
            {
                return false;
            }
            return WeblcmsRights :: get_instance()->is_allowed_in_courses_subtree(
                $right,
                $tool_registration->get_id(),
                WeblcmsRights :: TYPE_COURSE_MODULE,
                $this->get_course_id(),
                $id);
        }
    }

    public function get_user_id()
    {
        $va_id = Session :: get(self :: PARAM_VIEW_AS_ID);
        $course_id = Session :: get(self :: PARAM_VIEW_AS_COURSE_ID);
        // fake the id with the set "login as id" only if we're in the right
        // course
        if (isset($va_id) && isset($course_id))
        {
            if ($course_id == $this->get_course_id())
            {
                return $va_id;
            }
        }
        return parent :: get_user_id();
    }

    /**
     * Converts a tool name to the corresponding class name.
     *
     * @param $tool string The tool name.
     * @return string The class name.
     */
    public static function type_to_class($tool)
    {
        $toolName = (string) StringUtilities :: getInstance()->createString($tool)->upperCamelize();

        return __NAMESPACE__ . '\Implementation\\' . $toolName . '\\' . $toolName . 'Tool';
    }

    /**
     * Converts a tool class name to the corresponding tool name.
     *
     * @param $class string The class name.
     * @return string The tool name.
     */
    public static function class_to_type($class)
    {
        return (string) StringUtilities :: getInstance()->createString(str_replace('Tool', '', $class))->underscored()->__toString();
    }

    /**
     *
     * @see WeblcmsManager :: get_last_visit_date()
     */
    public function get_last_visit_date()
    {
        return $this->get_parent()->get_last_visit_date();
    }

    /**
     *
     * @see Application :: get_category()
     */
    public function get_category($id)
    {
        return $this->get_parent()->get_category($id);
    }

    public function display_introduction_text($introduction_text)
    {
        $html = array();

        if ($introduction_text)
        {
            if ($this->is_allowed(WeblcmsRights :: EDIT_RIGHT))
            {
                $toolbar = new Toolbar();

                $toolbar->add_item(
                    new ToolbarItem(
                        Translation :: get('Edit', null, Utilities :: COMMON_LIBRARIES),
                        Theme :: getInstance()->getCommonImagePath('Action/Edit'),
                        $this->get_url(
                            array(
                                self :: PARAM_ACTION => self :: ACTION_UPDATE_CONTENT_OBJECT,
                                self :: PARAM_PUBLICATION_ID => $introduction_text->get_id())),
                        ToolbarItem :: DISPLAY_ICON));
            }

            if ($this->is_allowed(WeblcmsRights :: DELETE_RIGHT))
            {
                if (! $toolbar)
                {
                    $toolbar = new Toolbar();
                }

                $toolbar->add_item(
                    new ToolbarItem(
                        Translation :: get('Delete', null, Utilities :: COMMON_LIBRARIES),
                        Theme :: getInstance()->getCommonImagePath('Action/Delete'),
                        $this->get_url(
                            array(
                                self :: PARAM_ACTION => self :: ACTION_DELETE,
                                self :: PARAM_PUBLICATION_ID => $introduction_text->get_id())),
                        ToolbarItem :: DISPLAY_ICON,
                        true));
            }

            $html[] = '<div class="announcements level_1" style="background-image: url(' .
                 Theme :: getInstance()->getImagePath(Introduction :: context(), 'Logo/' . Theme :: ICON_SMALL) . ');">';
            $html[] = '<div class="title" style="border-bottom: 1px dotted #D3D3D3; width:100%;">';
            $html[] = $introduction_text->get_content_object()->get_title();
            $html[] = '</div><div class="clear">&nbsp;</div>';
            $html[] = '<div class="description">';

            $content_object = $introduction_text->get_content_object();

            $rendition_implementation = ContentObjectRenditionImplementation :: factory(
                $content_object,
                ContentObjectRendition :: FORMAT_HTML,
                ContentObjectRendition :: VIEW_DESCRIPTION,
                $this);

            $html[] = $rendition_implementation->render();

            $html[] = '</div>';

            if ($toolbar)
            {
                $html[] = $toolbar->as_html() . '<div class="clear"></div>';
            }

            $html[] = '</div>';
            $html[] = '<br />';
        }

        return implode(PHP_EOL, $html);
    }

    private $introduction_cache;

    public function get_introduction_text()
    {
        $course_id = $this->get_course_id();
        $tool_id = $this->get_tool_id();

        if (is_null($this->introduction_cache[$course_id][$tool_id]))
        {
            $this->introduction_cache[$course_id][$tool_id] = false;

            $conditions = array();

            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectPublication :: class_name(),
                    ContentObjectPublication :: PROPERTY_COURSE_ID),
                new StaticConditionVariable($course_id));

            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectPublication :: class_name(),
                    ContentObjectPublication :: PROPERTY_TOOL),
                new StaticConditionVariable($tool_id));

            $condition = new AndCondition($conditions);

            $publication = \Chamilo\Application\Weblcms\Storage\DataManager :: retrieve_introduction_publication(
                $condition);
            if ($publication)
            {
                $this->introduction_cache[$course_id][$tool_id] = $publication;
            }
            else
            {
                $this->introduction_cache[$course_id][$tool_id] = false;
            }
        }
        return $this->introduction_cache[$course_id][$tool_id];
    }

    public static function get_allowed_types()
    {
        return array();
    }

    public function get_access_details_toolbar_item($parent)
    {
        if (Request :: get(self :: PARAM_PUBLICATION_ID))
        {
            $url = $this->get_parent()->get_url(
                array(
                    self :: PARAM_ACTION => self :: ACTION_VIEW_REPORTING_TEMPLATE,
                    self :: PARAM_PUBLICATION_ID => Request :: get(self :: PARAM_PUBLICATION_ID),
                    self :: PARAM_TEMPLATE_NAME => \Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Template\PublicationDetailTemplate :: class_name()));
            return new ToolbarItem(
                Translation :: get('AccessDetails'),
                Theme :: getInstance()->getCommonImagePath('Action/Reporting'),
                $url);
        }
        else
        {
            return new ToolbarItem('');
        }
    }

    public function get_complex_builder_url($pid)
    {
        return $this->get_url(
            array(
                self :: PARAM_ACTION => self :: ACTION_BUILD_COMPLEX_CONTENT_OBJECT,
                self :: PARAM_PUBLICATION_ID => $pid));
    }

    public function get_complex_display_url($pid)
    {
        return $this->get_url(
            array(
                self :: PARAM_ACTION => self :: ACTION_DISPLAY_COMPLEX_CONTENT_OBJECT,
                self :: PARAM_PUBLICATION_ID => $pid));
    }

    public static function get_pcattree_parents($pcattree)
    {
        $parent = \Chamilo\Application\Weblcms\Storage\DataManager :: retrieve_by_id(
            ContentObjectPublication :: class_name(),
            $pcattree);

        $parents[] = $parent;

        while ($parent && $parent->get_parent() != 0)
        {
            $parent = \Chamilo\Application\Weblcms\Storage\DataManager :: retrieve_by_id(
                ContentObjectPublication :: class_name(),
                $parent->get_parent());

            $parents[] = $parent;
        }
        $parents = array_reverse($parents);

        return $parents;
    }

    public function tool_has_new_publications($tool_name, $course)
    {
        return $this->get_parent()->tool_has_new_publications($tool_name, $course);
    }

    public static function get_tool_type_namespace($type)
    {
        return 'Chamilo\Application\Weblcms\Tool\Implementation\\' . $type;
    }

    public function get_entities()
    {
        $entities = array();
        $entities[CourseGroupEntity :: ENTITY_TYPE] = CourseGroupEntity :: get_instance($this->get_course_id());
        $entities[CourseUserEntity :: ENTITY_TYPE] = CourseUserEntity :: get_instance();
        $entities[CoursePlatformGroupEntity :: ENTITY_TYPE] = CoursePlatformGroupEntity :: get_instance();

        return $entities;
    }

    /**
     * Retrieves the location in the given category, or the location in the current category by publication id
     *
     * @param $category_id type
     * @return type
     */
    public function get_location($category_id = null)
    {
        $locations = $this->get_locations($category_id);
        return $locations[0];
    }

    /**
     * Retrieves the locations in the given category, or the locations in the current category by publication id
     *
     * @param $category_id type
     * @return type array
     */
    public function get_locations($category_id = null)
    {
        $course_id = $this->get_course_id();

        if (! isset($category_id))
        {
            $category_id = Request :: get(\Chamilo\Application\Weblcms\Manager :: PARAM_CATEGORY);
        }

        $publications = Request :: get(\Chamilo\Application\Weblcms\Manager :: PARAM_PUBLICATION);

        $rights_util = WeblcmsRights :: get_instance();

        if ($publications)
        {
            if (! is_array($publications))
            {
                $publications = array($publications);
            }

            foreach ($publications as $publication)
            {
                $locations[] = $rights_util->get_weblcms_location_by_identifier_from_courses_subtree(
                    WeblcmsRights :: TYPE_PUBLICATION,
                    $publication,
                    $course_id);
            }
        }
        else
        {
            if ($category_id)
            {
                $locations[] = $rights_util->get_weblcms_location_by_identifier_from_courses_subtree(
                    WeblcmsRights :: TYPE_COURSE_CATEGORY,
                    $category_id,
                    $course_id);
            }
            else
            {
                $course_tool = $this->get_parent()->get_tool_registration();
                $course_tool_name = $course_tool->get_name();

                if ($course_tool_name && $course_tool_name != 'Rights')
                {
                    $locations[] = $rights_util->get_weblcms_location_by_identifier_from_courses_subtree(
                        WeblcmsRights :: TYPE_COURSE_MODULE,
                        $course_tool->get_id(),
                        $course_id);
                }
                else
                {
                    $locations[] = $rights_util->get_courses_subtree_root($course_id);
                }
            }
        }

        return $locations;
    }

    public function get_content_object_display_attachment_url($attachment)
    {
        return $this->get_url(
            array(
                self :: PARAM_ACTION => self :: ACTION_VIEW_ATTACHMENT,
                self :: PARAM_OBJECT_ID => $attachment->get_id()));
    }

    public static function get_packages_from_filesystem()
    {
        $types = array();

        $directories = Filesystem :: get_directory_content(
            Path :: getInstance()->namespaceToFullPath(__NAMESPACE__ . '\Implementation'),
            Filesystem :: LIST_DIRECTORIES,
            false);

        foreach ($directories as $directory)
        {
            $namespace = __NAMESPACE__ . '\Implementation\\' . $directory;

            if (\Chamilo\Configuration\Package\Storage\DataClass\Package :: exists($namespace))
            {
                $types[] = $namespace;
            }
        }

        return $types;
    }

    public function run()
    {
        $factory = new ApplicationFactory(
            \Chamilo\Application\Weblcms\Tool\Action\Manager :: context(),
            new ApplicationConfiguration($this->getRequest(), $this->get_user(), $this));
        return $factory->run();
    }
}
