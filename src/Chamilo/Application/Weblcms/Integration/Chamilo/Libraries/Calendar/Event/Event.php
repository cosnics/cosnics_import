<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Libraries\Calendar\Event;

use Chamilo\Libraries\Calendar\Event\EventAttendee;
use Chamilo\Libraries\Calendar\Event\RecurrenceRules;

/**
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class Event extends \Chamilo\Core\Repository\Integration\Chamilo\Libraries\Calendar\Event\Event
{
    private ?int $courseId;

    public function __construct(
        ?string $id = null, ?int $startDate = null, ?int $endDate = null, ?RecurrenceRules $recurrenceRules = null,
        ?string $url = null, ?string $title = null, ?string $content = null, ?string $location = null,
        ?string $source = null, ?string $context = null, ?EventAttendee $organizer = null, $attendees = [], ?int $courseId = null
    )
    {
        parent::__construct(
            $id, $startDate, $endDate, $recurrenceRules, $url, $title, $content, $location, $source, $context,
            $organizer, $attendees
        );
        $this->courseId = $courseId;
    }

    public function getCourseId(): ?int
    {
        return $this->courseId;
    }

    public function setCourseId(?int $courseId): Event
    {
        $this->courseId = $courseId;

        return $this;
    }
}
