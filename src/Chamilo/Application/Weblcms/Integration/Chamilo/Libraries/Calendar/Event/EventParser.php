<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Libraries\Calendar\Event;

use Chamilo\Application\Weblcms\Course\Storage\DataClass\Course;
use Chamilo\Application\Weblcms\Manager;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Storage\DataManager;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Application\Routing\UrlGenerator;
use Chamilo\Libraries\DependencyInjection\DependencyInjectionContainerBuilder;
use Chamilo\Libraries\Translation\Translation;

/**
 * @package Chamilo\Application\Weblcms\Integration\Chamilo\Libraries\Calendar\Event
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class EventParser
{

    /**
     * @var int
     */
    private $fromDate;

    /**
     * @var \Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication
     */
    private $publication;

    /**
     * @var int
     */
    private $toDate;

    /**
     * @param \Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication $publication
     * @param int $fromDate
     * @param int $toDate
     */
    public function __construct(ContentObjectPublication $publication, $fromDate, $toDate)
    {
        $this->publication = $publication;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    /**
     * @return \Chamilo\Core\Repository\Integration\Chamilo\Libraries\Calendar\Event\Event[]
     */
    public function getEvents()
    {
        $course = DataManager::retrieve_by_id(
            Course::class, $this->getPublication()->get_course_id()
        );

        $parser = \Chamilo\Core\Repository\Integration\Chamilo\Libraries\Calendar\Event\EventParser::factory(
            $this->getPublication()->get_content_object(), $this->getFromDate(), $this->getToDate(), Event::class
        );

        $events = $parser->getEvents();

        foreach ($events as $parsedEvent)
        {
            $parameters = [];
            $parameters[Application::PARAM_CONTEXT] = Manager::CONTEXT;
            $parameters[Application::PARAM_ACTION] = Manager::ACTION_VIEW_COURSE;
            $parameters[Manager::PARAM_TOOL_ACTION] = \Chamilo\Application\Weblcms\Tool\Manager::ACTION_VIEW;
            $parameters[Manager::PARAM_COURSE] = $this->getPublication()->get_course_id();
            $parameters[Manager::PARAM_TOOL] = $this->getPublication()->get_tool();
            $parameters[Manager::PARAM_PUBLICATION] = $this->getPublication()->get_id();

            $link = $this->getUrlGenerator()->fromParameters($parameters);

            $parsedEvent->setUrl($link);
            $parsedEvent->setSource(
                Translation::get('Course', null, Manager::CONTEXT) . ' - ' . $course->get_title()
            );
            $parsedEvent->setId($this->getPublication()->get_id());
            $parsedEvent->setContext(Manager::CONTEXT);
            $parsedEvent->setCourseId($this->getPublication()->get_course_id());

            $result[] = $parsedEvent;
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getFromDate()
    {
        return $this->fromDate;
    }

    /**
     * @param int $fromDate
     */
    public function setFromDate($fromDate)
    {
        $this->fromDate = $fromDate;
    }

    /**
     * @return \Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication
     */
    public function getPublication()
    {
        return $this->publication;
    }

    /**
     * @param \Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication $publication
     */
    public function setPublication($publication)
    {
        $this->publication = $publication;
    }

    /**
     * @return int
     */
    public function getToDate()
    {
        return $this->toDate;
    }

    /**
     * @param int $toDate
     */
    public function setToDate($toDate)
    {
        $this->toDate = $toDate;
    }

    public function getUrlGenerator(): UrlGenerator
    {
        return DependencyInjectionContainerBuilder::getInstance()->createContainer()->get(UrlGenerator::class);
    }
}
