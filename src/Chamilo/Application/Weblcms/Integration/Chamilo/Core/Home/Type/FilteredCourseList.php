<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Core\Home\Type;

use Chamilo\Application\Weblcms\CourseType\Storage\DataClass\CourseType;
use Chamilo\Application\Weblcms\CourseType\Storage\DataManager as CourseTypeDataManager;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Home\Block;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Core\Home\Architecture\ConfigurableInterface;

/**
 * This class represents a block to show the course list filtered in a given course type and optionally a given category
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class FilteredCourseList extends Block implements ConfigurableInterface
{
    const CONFIGURATION_SHOW_NEW_ICONS = 'show_new_icons';
    const CONFIGURATION_COURSE_TYPE = 'course_type';

    /**
     * **************************************************************************************************************
     * Parameters *
     * **************************************************************************************************************
     */
    const PARAM_COURSE_TYPE = 'course_type';

    /**
     * **************************************************************************************************************
     * Properties *
     * **************************************************************************************************************
     */

    /**
     * The cached course type id
     *
     * @var int
     */
    private $courseTypeId;

    /**
     * The cached user course category id
     *
     * @var int
     */
    private $userCourseCategoryId;

    private $courseListRenderer;

    /**
     * **************************************************************************************************************
     * Inherited Functionality *
     * **************************************************************************************************************
     */

    /**
     * Constructs the
     *
     * @param mixed $parent
     * @param Block $block_info
     */
    public function __construct($renderer, $block)
    {
        parent :: __construct($renderer, $block);

        $this->loadSettings();
    }

    public function toHtml($view = '')
    {
        if ($this->getCourseListRenderer()->get_courses()->size() > 0)
        {
            return parent :: toHtml($view);
        }
        else
        {
            return '';
        }
    }

    public function getCourseListRenderer()
    {
        if (! isset($this->courseListRenderer))
        {
            $this->courseListRenderer = new \Chamilo\Application\Weblcms\Renderer\CourseList\Type\FilteredCourseListRenderer(
                $this,
                $this->getLinkTarget(),
                $this->getCourseTypeId(),
                $this->getUserCourseCategoryId());
        }

        return $this->courseListRenderer;
    }

    /**
     * Displays the content
     *
     * @return string
     */
    public function displayContent()
    {
        $html = array();

        $renderer = $this->getCourseListRenderer();

        if ($this->getBlock()->getSetting(self :: CONFIGURATION_SHOW_NEW_ICONS, true))
        {
            $renderer->show_new_publication_icons();
        }

        $html[] = $renderer->as_html();

        if (! $this->getBlock()->getSetting(self :: CONFIGURATION_SHOW_NEW_ICONS, true))
        {
            $courseTypeLink = new Redirect(
                array(
                    Application :: PARAM_CONTEXT => \Chamilo\Application\Weblcms\Manager :: package(),
                    \Chamilo\Application\Weblcms\Renderer\CourseList\Type\CourseTypeCourseListRenderer :: PARAM_SELECTED_COURSE_TYPE => $this->getCourseTypeId()));

            $html[] = '<div style="margin-top: 15px;">';
            $html[] = Translation :: get('CheckWhatsNew', array('URL' => $courseTypeLink->getUrl()));
            $html[] = '</div>';
        }

        return implode(PHP_EOL, $html);
    }

    /**
     * Returns the title of this block Changes the default title of the block to the title of the course type and
     * (optionally) the title of the selected user course category
     *
     * @return string
     */
    public function getTitle()
    {
        $course_type_id = $this->getCourseTypeId();

        if ($course_type_id > 0)
        {
            $course_type = CourseTypeDataManager :: retrieve_by_id(CourseType :: class_name(), $course_type_id);

            if ($course_type)
            {
                $course_type_title = $course_type->get_title();
            }
            else
            {
                return Translation :: get('NoSuchCourseType');
            }
        }
        else
        {
            $course_type_title = Translation :: get('NoCourseType');
        }

        $user_course_category_id = $this->getUserCourseCategoryId();

        if ($user_course_category_id > 0)
        {

            $course_user_category = \Chamilo\Application\Weblcms\Storage\DataManager :: retrieve_by_id(
                \Chamilo\Application\Weblcms\Storage\DataClass\CourseUserCategory :: class_name(),
                $user_course_category_id);

            if ($course_user_category)
            {
                $course_user_category_title = ' - ' . $course_user_category->get_title();
            }
        }

        return $course_type_title . $course_user_category_title;
    }

    /**
     * **************************************************************************************************************
     * Helper Functionality *
     * **************************************************************************************************************
     */

    /**
     * Returns the selected course type id
     *
     * @return int
     */
    public function getCourseTypeId()
    {
        return $this->courseTypeId;
    }

    /**
     * Returns the selected user course category id (if any)
     *
     * @return int
     */
    public function getUserCourseCategoryId()
    {
        return $this->userCourseCategoryId;
    }

    /**
     * Loads the settings of this block
     */
    private function loadSettings()
    {
        $this->courseTypeId = $this->getBlock()->getSetting(self :: CONFIGURATION_COURSE_TYPE);
        // TODO: Fix this?
        $this->userCourseCategoryId = null;
    }

    /**
     *
     * @see \Chamilo\Core\Home\Architecture\ConfigurableInterface::getConfigurationVariables()
     */
    public function getConfigurationVariables()
    {
        return array(self :: CONFIGURATION_SHOW_NEW_ICONS, self :: CONFIGURATION_COURSE_TYPE);
    }
}
