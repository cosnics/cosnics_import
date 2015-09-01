<?php
namespace Chamilo\Libraries\Calendar\Renderer\Service;

use Chamilo\Libraries\Calendar\Event\RecurrenceCalculator;
use Chamilo\Libraries\Calendar\Renderer\Interfaces\VisibilitySupport;
use Chamilo\Libraries\Calendar\Event\Interfaces\ActionSupport;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Architecture\ClassnameUtilities;

/**
 *
 * @package Chamilo\Application\Calendar\Service
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
abstract class CalendarRendererProvider implements
    \Chamilo\Libraries\Calendar\Renderer\Interfaces\CalendarRendererProviderInterface
{
    const SOURCE_TYPE_INTERNAL = 1;
    const SOURCE_TYPE_EXTERNAL = 2;
    const SOURCE_TYPE_BOTH = 3;

    /**
     *
     * @var \Chamilo\Core\User\Storage\DataClass\User
     */
    private $dataUser;

    /**
     *
     * @var \Chamilo\Core\User\Storage\DataClass\User
     */
    private $viewingUser;

    /**
     *
     * @var string[]
     */
    private $displayParameters;

    /**
     *
     * @param \Chamilo\Core\User\Storage\DataClass\User $dataUser
     * @param \Chamilo\Core\User\Storage\DataClass\User $viewingUser
     * @param string[] $displayParameters;
     */
    public function __construct(User $dataUser, User $viewingUser, $displayParameters)
    {
        $this->dataUser = $dataUser;
        $this->viewingUser = $viewingUser;
        $this->displayParameters = $displayParameters;
    }

    /**
     *
     * @return \Chamilo\Core\User\Storage\DataClass\User
     */
    public function getDataUser()
    {
        return $this->dataUser;
    }

    /**
     *
     * @param \Chamilo\Core\User\Storage\DataClass\User $dataUser
     */
    public function setDataUser(User $dataUser)
    {
        $this->dataUser = $dataUser;
    }

    /**
     *
     * @return \Chamilo\Core\User\Storage\DataClass\User
     */
    public function getViewingUser()
    {
        return $this->viewingUser;
    }

    /**
     *
     * @param \Chamilo\Core\User\Storage\DataClass\User $viewingUser
     */
    public function setViewingUser(User $viewingUser)
    {
        $this->viewingUser = $viewingUser;
    }

    /**
     *
     * @return string[]
     */
    public function getDisplayParameters()
    {
        return $this->displayParameters;
    }

    /**
     *
     * @param string[] $displayParameters
     */
    public function setDisplayParameters($displayParameters)
    {
        $this->displayParameters = $displayParameters;
    }

    /**
     *
     * @see \Chamilo\Libraries\Calendar\Renderer\Interfaces\CalendarRendererProviderInterface::getInternalEventsInPeriod()
     */
    public function getInternalEventsInPeriod(\Chamilo\Libraries\Calendar\Renderer\Renderer $renderer, $startTime,
        $endTime, $calculateRecurrence = true)
    {
        return $this->getEvents($renderer, self :: SOURCE_TYPE_INTERNAL, $startTime, $endTime, $calculateRecurrence);
    }

    /**
     *
     * @see \Chamilo\Libraries\Calendar\Renderer\Interfaces\CalendarRendererProviderInterface::getExternalInPeriod()
     */
    public function getExternalEventsInPeriod(\Chamilo\Libraries\Calendar\Renderer\Renderer $renderer, $startTime,
        $endTime, $calculateRecurrence = true)
    {
        return $this->getEvents($renderer, self :: SOURCE_TYPE_EXTERNAL, $startTime, $endTime, $calculateRecurrence);
    }

    /**
     *
     * @see \Chamilo\Libraries\Calendar\Renderer\Interfaces\CalendarRendererProviderInterface::getAllEventsInPeriod()
     */
    public function getAllEventsInPeriod(\Chamilo\Libraries\Calendar\Renderer\Renderer $renderer, $startTime, $endTime,
        $calculateRecurrence = true)
    {
        return $this->getEvents($renderer, self :: SOURCE_TYPE_BOTH, $startTime, $endTime, $calculateRecurrence);
    }

    /**
     *
     * @see \Chamilo\Libraries\Calendar\Renderer\Interfaces\CalendarRendererProviderInterface::getInternalEvents()
     */
    public function getInternalEvents(\Chamilo\Libraries\Calendar\Renderer\Renderer $renderer)
    {
        return $this->getEvents($renderer, self :: SOURCE_TYPE_INTERNAL);
    }

    /**
     *
     * @see \Chamilo\Libraries\Calendar\Renderer\Interfaces\CalendarRendererProviderInterface::getExternal()
     */
    public function getExternalEvents(\Chamilo\Libraries\Calendar\Renderer\Renderer $renderer)
    {
        return $this->getEvents($renderer, self :: SOURCE_TYPE_EXTERNAL);
    }

    /**
     *
     * @see \Chamilo\Libraries\Calendar\Renderer\Interfaces\CalendarRendererProviderInterface::getAllEvents()
     */
    public function getAllEvents(\Chamilo\Libraries\Calendar\Renderer\Renderer $renderer)
    {
        return $this->getEvents($renderer, self :: SOURCE_TYPE_BOTH);
    }

    /**
     *
     * @see \Chamilo\Libraries\Calendar\Renderer\Interfaces\CalendarRendererProviderInterface::getEvents()
     */
    private function getEvents(\Chamilo\Libraries\Calendar\Renderer\Renderer $renderer, $sourceType, $startTime = null,
        $endTime = null, $calculateRecurrence = false)
    {
        $events = $this->aggregateEvents($renderer, $sourceType, $startTime, $endTime);

        if ($startTime && $endTime && $calculateRecurrence)
        {
            $recurringEvents = array();

            foreach ($events as $event)
            {
                $recurrenceCalculator = new RecurrenceCalculator($event, $startTime, $endTime);
                $parsedEvents = $recurrenceCalculator->getEvents();

                foreach ($parsedEvents as $parsedEvent)
                {
                    $recurringEvents[] = $parsedEvent;
                }
            }

            return $recurringEvents;
        }
        else
        {
            return $events;
        }
    }

    /**
     *
     * @param \Chamilo\Libraries\Calendar\Renderer\Renderer $renderer
     * @param integer $sourceType
     * @param integer $startTime
     * @param integer $endTime
     */
    abstract public function aggregateEvents(\Chamilo\Libraries\Calendar\Renderer\Renderer $renderer, $sourceType,
        $startTime, $endTime);

    /**
     *
     * @return boolean
     */
    public function supportsVisibility()
    {
        if ($this instanceof VisibilitySupport)
        {
            $ajaxVisibilityClassName = ClassnameUtilities :: getInstance()->getNamespaceParent(
                $this->getVisibilityContext()) . '\Ajax\Component\CalendarEventVisibilityComponent';

            if (! class_exists($ajaxVisibilityClassName))
            {
                throw new \Exception(
                    'Please add an ajax Class CalendarEventVisibilityComponent to your implementing context\'s Ajax subpackage (' .
                         $this->getVisibilityContext() .
                         '). This class should extend the abstract \Chamilo\Libraries\Calendar\Event\Ajax\Component\CalendarEventVisibilityComponent class.');
            }

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     *
     * @return boolean
     */
    public function supportsActions()
    {
        return $this instanceof ActionSupport;
    }

    /**
     *
     * @param integer $source
     * @return boolean
     */
    public function isInternalSource($source)
    {
        return $this->matchesRequestedSource(self :: SOURCE_TYPE_INTERNAL, $source);
    }

    /**
     *
     * @param integer $source
     * @return boolean
     */
    public function isExternalSource($source)
    {
        return $this->matchesRequestedSource(self :: SOURCE_TYPE_EXTERNAL, $source);
    }

    /**
     *
     * @param integer $requestedSource
     * @param integer $implementationSource
     * @return boolean
     */
    public function matchesRequestedSource($requestedSource, $implementationSource)
    {
        return (boolean) ($requestedSource & $implementationSource);
    }
}