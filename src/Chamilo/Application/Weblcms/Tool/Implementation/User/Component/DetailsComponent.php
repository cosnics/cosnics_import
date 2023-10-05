<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\User\Component;

use Chamilo\Application\Weblcms\Tool\Implementation\User\Manager;
use Chamilo\Application\Weblcms\Tool\Implementation\User\UserCourseGroups;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Core\User\UserDetails;
use Chamilo\Core\User\UserGroups;
use Chamilo\Libraries\Storage\DataManager\DataManager;

/**
 * @package application.lib.weblcms.tool.user.component
 */
class DetailsComponent extends Manager
{

    public function run()
    {
        $html = [];

        $html[] = $this->render_header();

        $availableGroups =
            \Chamilo\Application\Weblcms\Course\Storage\DataManager::retrieve_all_subscribed_platform_groups(
                [$this->get_course_id()]
            );

        if ($this->getRequest()->query->has(\Chamilo\Application\Weblcms\Manager::PARAM_USERS))
        {
            /** @var \Chamilo\Core\User\Storage\DataClass\User $user */
            $user = DataManager::retrieve_by_id(
                User::class, $this->getRequest()->query->get(\Chamilo\Application\Weblcms\Manager::PARAM_USERS)
            );

            $details = new UserDetails($user);
            $groups = new UserGroups($user->get_id(), true, $availableGroups);
            $course_groups = new UserCourseGroups($user->get_id(), $this->get_course_id());

            $html[] = $details->toHtml();
            $html[] = $groups->toHtml();
            $html[] = $course_groups->toHtml();
        }

        if (isset($_POST['user_id']))
        {
            foreach ($_POST['user_id'] as $user_id)
            {
                $user = DataManager::retrieve_by_id(
                    User::class, $user_id
                );
                $details = new UserDetails($user);
                $groups = new UserGroups($user->get_id(), true, $availableGroups);
                $course_groups = new UserCourseGroups($user->get_id(), $this->get_course_id());

                $html[] = $details->toHtml();
                $html[] = $groups->toHtml();
                $html[] = $course_groups->toHtml();
            }
        }

        $html[] = $this->render_footer();

        return implode(PHP_EOL, $html);
    }

    public function getAdditionalParameters(array $additionalParameters = []): array
    {
        $additionalParameters[] = \Chamilo\Application\Weblcms\Manager::PARAM_USERS;
        $additionalParameters[] = self::PARAM_TAB;

        return parent::getAdditionalParameters($additionalParameters);
    }
}
