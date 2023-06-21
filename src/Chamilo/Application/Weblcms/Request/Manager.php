<?php
namespace Chamilo\Application\Weblcms\Request;

use Chamilo\Application\Weblcms\CourseType\Storage\DataManager;
use Chamilo\Application\Weblcms\Rights\CourseManagementRights;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Mail\Mailer\MailerInterface;

abstract class Manager extends Application
{
    public const ACTION_BROWSE = 'Browser';
    public const ACTION_CREATE = 'Creator';
    public const ACTION_DELETE = 'Deleter';
    public const ACTION_DENY = 'Denier';
    public const ACTION_GRANT = 'Granter';
    public const ACTION_RIGHTS = 'Rights';

    public const CONTEXT = __NAMESPACE__;

    public const DEFAULT_ACTION = self::ACTION_BROWSE;

    public const PARAM_ACTION = 'request_action';
    public const PARAM_REQUEST_ID = 'request_id';
    public const PARAM_RESET_CACHE = 'reset_cache';

    protected function getActiveMailer(): MailerInterface
    {
        return $this->getService('Chamilo\Libraries\Mail\Mailer\ActiveMailer');
    }

    public function request_allowed()
    {
        if ($this->get_user()->isPlatformAdmin())
        {
            return true;
        }

        $course_types = DataManager::retrieve_active_course_types();
        foreach ($course_types as $course_type)
        {
            if (CourseManagementRights::getInstance()->is_allowed_management(
                CourseManagementRights::REQUEST_COURSE_RIGHT, $course_type->get_id(),
                CourseManagementRights::TYPE_COURSE_TYPE
            ))
            {
                return true;
            }
        }

        return false;
    }
}