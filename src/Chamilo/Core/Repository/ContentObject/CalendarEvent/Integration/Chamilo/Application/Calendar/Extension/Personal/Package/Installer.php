<?php
namespace Chamilo\Core\Repository\ContentObject\CalendarEvent\Integration\Chamilo\Application\Calendar\Extension\Personal\Package;

use Chamilo\Configuration\Package\NotAllowed;

/**
 * @package core\repository\content_object\calendar_event
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class Installer extends \Chamilo\Configuration\Package\Action\Installer implements NotAllowed
{
    public const CONTEXT = 'Chamilo\Core\Repository\ContentObject\CalendarEvent\Integration\Chamilo\Application\Calendar\Extension\Personal';
}
