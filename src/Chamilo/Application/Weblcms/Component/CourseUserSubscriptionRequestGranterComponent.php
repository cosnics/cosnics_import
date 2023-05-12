<?php
namespace Chamilo\Application\Weblcms\Component;

use Chamilo\Application\Weblcms\Course\Storage\DataManager as CourseDataManager;
use Chamilo\Application\Weblcms\Manager;
use Chamilo\Application\Weblcms\Storage\DataClass\CourseRequest;
use Chamilo\Application\Weblcms\Storage\DataManager;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * Class CourseUserSubscriptionRequestGranterComponent
 */
class CourseUserSubscriptionRequestGranterComponent extends Manager
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $this->checkAuthorization(Manager::CONTEXT, 'ManageCourses');
        
        $requestIds = $this->getRequest()->getFromRequestOrQuery(Manager::PARAM_REQUEST);
        
        if (empty($requestIds))
        {
            return $this->display_error_page(
                htmlentities(
                    Translation::get(
                        'NoObjectSelected', 
                        array('OBJECT' => Translation::get('Request')), 
                        StringUtilities::LIBRARIES)));
        }
        
        if (! is_array($requestIds))
        {
            $requestIds = array($requestIds);
        }
        
        $failures = 0;
        
        foreach ($requestIds as $requestId)
        {
            /**
             *
             * @var CourseRequest $request
             */
            $request = DataManager::retrieve_by_id(CourseRequest::class, (int) $requestId);
            if (! CourseDataManager::subscribe_user_to_course($request->get_course_id(), '5', $request->get_user_id()))
            {
                $failures ++;
            }
            else
            {
                $request->set_decision(CourseRequest::ALLOWED_DECISION);
                $request->set_decision_date(time());
                
                if (! $request->update())
                {
                    $failures ++;
                }
            }
        }
        
        if ($failures)
        {
            $message = 'ObjectsNotGranted';
            $parameter = array('OBJECTS' => Translation::get('Requests'));
        }
        else
        {
            $message = 'ObjectsGranted';
            $parameter = array('OBJECTS' => Translation::get('Requests'));
        }
        
        $this->redirectWithMessage(
            Translation::getInstance()->getTranslation($message, $parameter, StringUtilities::LIBRARIES),
            (bool) $failures,
            array(self::PARAM_ACTION => self::ACTION_ADMIN_REQUEST_BROWSER));
    }
}
