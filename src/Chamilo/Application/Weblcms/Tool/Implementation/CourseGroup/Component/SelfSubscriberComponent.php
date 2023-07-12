<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\CourseGroup\Component;

use Chamilo\Application\Weblcms\Tool\Implementation\CourseGroup\Manager;
use Chamilo\Application\Weblcms\Tool\Implementation\CourseGroup\Storage\DataManager;
use Chamilo\Libraries\Architecture\Exceptions\ObjectNotExistException;
use Chamilo\Libraries\Translation\Translation;

/**
 * @package application.lib.weblcms.tool.course_group.component
 */
class SelfSubscriberComponent extends Manager
{

    public function run()
    {
        $course_group = $this->get_course_group();

        if (empty($course_group))
        {
            throw new ObjectNotExistException(
                Translation::getInstance()->getTranslation('CourseGroup'),
                $this->getRequest()->query->get(self::PARAM_COURSE_GROUP)
            );
        }

        if ($course_group->is_self_registration_allowed() &&
            ($course_group->count_members() < $course_group->get_max_number_of_members() ||
                $course_group->get_max_number_of_members() == 0) && !$course_group->is_member($this->get_user()) &&
            DataManager::more_subscriptions_allowed_for_user_in_group(
                $course_group->get_parent_id(), $this->get_user_id()
            ))
        {
            $course_group->subscribe_users($this->get_user());
            $this->getCourseGroupDecoratorsManager()->subscribeUser($course_group, $this->getUser());

            $this->redirectWithMessage(
                Translation::get('UserSubscribed'), false,
                [\Chamilo\Application\Weblcms\Tool\Manager::PARAM_ACTION => self::ACTION_GROUP_DETAILS]
            );
        }
        else
        {
            $this->redirectWithMessage(
                Translation::get('UserNotSubscribed'), true, [
                    \Chamilo\Application\Weblcms\Tool\Manager::PARAM_ACTION => self::ACTION_GROUP_DETAILS,
                    self::PARAM_COURSE_GROUP => $course_group->get_parent_id()
                ]
            );
        }
    }
}
