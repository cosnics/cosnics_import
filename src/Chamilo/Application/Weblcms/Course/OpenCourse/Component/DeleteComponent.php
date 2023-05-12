<?php
namespace Chamilo\Application\Weblcms\Course\OpenCourse\Component;

use Chamilo\Application\Weblcms\Course\OpenCourse\Manager;
use Chamilo\Libraries\Translation\Translation;
use Exception;

/**
 * Component to remove open access to an existing course
 * 
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class DeleteComponent extends Manager
{

    /**
     * Runs this component and returns it's output
     */
    public function run()
    {
        $this->checkAuthorization(Manager::CONTEXT, 'ManageOpenCourses');
        
        $courseIds = $this->getCourseIdsFromRequest();
        
        try
        {
            $this->getOpenCourseService()->removeCoursesAsOpenCourse($this->getUser(), $courseIds);
            $success = true;
            $redirectMessageVariable = 'CoursesRemovedFromOpenCourseList';
        }
        catch (Exception $ex)
        {
            $success = false;
            $redirectMessageVariable = 'CoursesNotRemovedFromOpenCourseList';
        }
        
        $this->redirectWithMessage(
            Translation::getInstance()->getTranslation($redirectMessageVariable, null, Manager::CONTEXT),
            ! $success, 
            array(self::PARAM_ACTION => self::ACTION_BROWSE));
    }

    /**
     * Returns the list of additional parameters that need to be registered
     * 
     * @return string[]
     */
    public function getAdditionalParameters(array $additionalParameters = []): array
    {
        $additionalParameters[] = self::PARAM_COURSE_ID;

        return parent::getAdditionalParameters($additionalParameters);
    }
}