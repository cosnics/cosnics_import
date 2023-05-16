<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Event;

use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataClass\CourseChange;
use Chamilo\Core\Tracking\Storage\DataClass\Event;

/**
 * @package Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Event
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class DeleteCourse extends Event
{
    public const CONTEXT = 'Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking';

    /**
     * @see \Chamilo\Core\Tracking\Storage\DataClass\Event::getTrackerClasses()
     */
    public function getTrackerClasses()
    {
        return [
            CourseChange::class
        ];
    }
}