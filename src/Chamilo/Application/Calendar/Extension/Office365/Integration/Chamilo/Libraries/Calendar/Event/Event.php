<?php
namespace Chamilo\Application\Calendar\Extension\Office365\Integration\Chamilo\Libraries\Calendar\Event;

/**
 * @package Chamilo\Application\Calendar\Extension\Office365\Integration\Chamilo\Libraries\Calendar\Event
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class Event extends \Chamilo\Libraries\Calendar\Event\Event
{
    private \Microsoft\Graph\Model\Event $sourceEvent;

    public function getSourceEvent(): \Microsoft\Graph\Model\Event
    {
        return $this->sourceEvent;
    }

    public function setSourceEvent(\Microsoft\Graph\Model\Event $office365CalendarEvent): static
    {
        $this->sourceEvent = $office365CalendarEvent;

        return $this;
    }
}
