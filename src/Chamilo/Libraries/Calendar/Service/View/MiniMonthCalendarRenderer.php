<?php
namespace Chamilo\Libraries\Calendar\Service\View;

use Chamilo\Libraries\Architecture\Application\Routing\UrlGenerator;
use Chamilo\Libraries\Calendar\Architecture\Interfaces\CalendarRendererProviderInterface;
use Chamilo\Libraries\Calendar\Event\Event;
use Chamilo\Libraries\Calendar\Service\Event\EventMiniMonthRenderer;
use Chamilo\Libraries\Calendar\Service\LegendRenderer;
use Chamilo\Libraries\Calendar\Service\View\TableBuilder\CalendarTableBuilder;
use Chamilo\Libraries\Calendar\Service\View\TableBuilder\MiniMonthCalendarTableBuilder;
use Chamilo\Libraries\File\WebPathBuilder;
use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;
use Chamilo\Libraries\Format\Utilities\ResourceManager;
use Chamilo\Libraries\Utilities\StringUtilities;
use Symfony\Component\Translation\Translator;

/**
 * @package Chamilo\Libraries\Calendar\Service\View
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class MiniMonthCalendarRenderer extends MiniCalendarRenderer
{
    protected EventMiniMonthRenderer $eventMiniMonthRenderer;

    protected MiniMonthCalendarTableBuilder $miniMonthCalendarTableBuilder;

    protected ResourceManager $resourceManager;

    protected WebPathBuilder $webPathBuilder;

    public function __construct(
        LegendRenderer $legendRenderer, UrlGenerator $urlGenerator, Translator $translator,
        EventMiniMonthRenderer $eventMiniMonthRenderer, MiniMonthCalendarTableBuilder $miniMonthCalendarTableBuilder,
        WebPathBuilder $webPathBuilder, ResourceManager $resourceManager
    )
    {
        parent::__construct($legendRenderer, $urlGenerator, $translator);

        $this->eventMiniMonthRenderer = $eventMiniMonthRenderer;
        $this->miniMonthCalendarTableBuilder = $miniMonthCalendarTableBuilder;
        $this->webPathBuilder = $webPathBuilder;
        $this->resourceManager = $resourceManager;
    }

    /**
     * @throws \Exception
     */
    public function render(CalendarRendererProviderInterface $dataProvider, int $displayTime, array $viewActions = []
    ): string
    {
        $html = [];

        $html[] = '<div class="panel panel-default">';
        $html[] = $this->renderNavigation($dataProvider, $displayTime);

        $html[] = '<div class="table-calendar-mini-container">';
        $html[] = $this->renderCalendar($dataProvider, $displayTime);
        $html[] = '</div>';
        $html[] = '<div class="clearfix"></div>';

        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

    public function getEventMiniMonthRenderer(): EventMiniMonthRenderer
    {
        return $this->eventMiniMonthRenderer;
    }

    public function getMiniMonthCalendarTableBuilder(): MiniMonthCalendarTableBuilder
    {
        return $this->miniMonthCalendarTableBuilder;
    }

    public function getResourceManager(): ResourceManager
    {
        return $this->resourceManager;
    }

    public function getWebPathBuilder(): WebPathBuilder
    {
        return $this->webPathBuilder;
    }

    public function isFadedEvent(int $displayTime, Event $event): bool
    {
        $startDate = $event->getStartDate();

        $fromDate = strtotime(date('Y-m-1', $displayTime));
        $toDate = strtotime('-1 Second', strtotime('Next Month', $fromDate));

        return $startDate < $fromDate || $startDate > $toDate;
    }

    /**
     * @throws \Exception
     */
    public function renderCalendar(CalendarRendererProviderInterface $dataProvider, int $displayTime): string
    {
        $calendarTableBuilder = $this->getMiniMonthCalendarTableBuilder();

        $startTime = $calendarTableBuilder->getTableStartTime($displayTime);
        $endTime = $calendarTableBuilder->getTableEndTime($displayTime);

        $events = $this->getEvents($dataProvider, $startTime, $endTime);
        $tableDate = $startTime;
        $eventsToShow = [];

        while ($tableDate <= $endTime)
        {
            $nextTableDate = strtotime('+1 Day', $tableDate);

            foreach ($events as $event)
            {
                $startDate = $event->getStartDate();
                $endDate = $event->getEndDate();

                if ($tableDate < $startDate && $startDate < $nextTableDate ||
                    $tableDate < $endDate && $endDate <= $nextTableDate ||
                    $startDate <= $tableDate && $nextTableDate <= $endDate)
                {
                    $this->getLegendRenderer()->addSource($event->getSource());

                    $eventsToShow[$tableDate][] = $this->getEventMiniMonthRenderer()->render(
                        $event, $tableDate, $nextTableDate, $this->isEventSourceVisible($dataProvider, $event),
                        $this->isFadedEvent($displayTime, $event)
                    );
                }
            }

            $tableDate = $nextTableDate;
        }

        $html = [];

        $html[] = '<div class="table-calendar-mini-container">';
        $html[] = $calendarTableBuilder->render($displayTime, $eventsToShow, ['table-calendar-mini'],
            $this->determineNavigationUrl($dataProvider));
        $html[] = '</div>';
        $html[] = '<div class="clearfix"></div>';

        $html[] = $this->getResourceManager()->getResourceHtml(
            $this->getWebPathBuilder()->getJavascriptPath('Chamilo\Libraries\Calendar') . 'EventTooltip.js'
        );

        return implode(PHP_EOL, $html);
    }

    public function renderNavigation(CalendarRendererProviderInterface $dataProvider, int $displayTime): string
    {
        $html = [];

        $html[] = '<div class="panel-heading table-calendar-mini-navigation">';
        $html[] = $this->renderPreviousMonthNavigation($dataProvider, $displayTime);
        $html[] = $this->renderNextMonthNavigation($dataProvider, $displayTime);
        $html[] = '<h4 class="panel-title">';
        $html[] = $this->renderTitle($dataProvider, $displayTime);
        $html[] = '</h4>';
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

    public function renderNextMonthNavigation(CalendarRendererProviderInterface $dataProvider, int $displayTime): string
    {
        $urlFormat = $this->determineNavigationUrl($dataProvider);
        $nextTime = strtotime('+1 Month', $displayTime);
        $nextUrl = str_replace(CalendarTableBuilder::TIME_PLACEHOLDER, $nextTime, $urlFormat);

        $glyph = new FontAwesomeGlyph('chevron-right', ['pull-right'], null, 'fas');

        return '<a href="' . $nextUrl . '">' . $glyph->render() . '</a>';
    }

    public function renderPreviousMonthNavigation(CalendarRendererProviderInterface $dataProvider, int $displayTime
    ): string
    {
        $urlFormat = $this->determineNavigationUrl($dataProvider);
        $previousTime = strtotime('-1 Month', $displayTime);
        $previousUrl = str_replace(CalendarTableBuilder::TIME_PLACEHOLDER, $previousTime, $urlFormat);

        $glyph = new FontAwesomeGlyph('chevron-left', ['pull-left'], null, 'fas');

        return '<a href="' . $previousUrl . '">' . $glyph->render() . '</a>';
    }

    public function renderTitle(CalendarRendererProviderInterface $dataProvider, int $displayTime): string
    {
        return $this->getTranslator()->trans(date('F', $displayTime) . 'Long', [], StringUtilities::LIBRARIES) . ' ' .
            date('Y', $displayTime);
    }
}
