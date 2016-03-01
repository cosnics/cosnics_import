<?php
namespace Chamilo\Application\Calendar\Component;

use Chamilo\Application\Calendar\Manager;
use Chamilo\Application\Calendar\Repository\CalendarRendererProviderRepository;
use Chamilo\Application\Calendar\Service\CalendarRendererProvider;
use Chamilo\Configuration\Configuration;
use Chamilo\Configuration\Storage\DataClass\Registration;
use Chamilo\Core\User\Component\UserSettingsComponent;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\Calendar\Renderer\Form\JumpForm;
use Chamilo\Libraries\Calendar\Renderer\Legend;
use Chamilo\Libraries\Calendar\Renderer\Type\View\MiniMonthRenderer;
use Chamilo\Libraries\Calendar\Renderer\Type\ViewRenderer;
use Chamilo\Libraries\Calendar\Renderer\Type\ViewRendererFactory;
use Chamilo\Libraries\Calendar\Table\Type\MiniMonthCalendar;
use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Format\Structure\ActionBar\Button;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonGroup;
use Chamilo\Libraries\Format\Structure\Page;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Configuration\LocalSetting;
use Chamilo\Libraries\Platform\Translation;

/**
 *
 * @package Chamilo\Application\Calendar\Component
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class BrowserComponent extends Manager implements DelegateComponent
{

    /**
     *
     * @var JumpForm
     */
    private $form;

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $header = Page :: getInstance()->getHeader();
        $header->addCssFile(Theme :: getInstance()->getCssPath(self :: package(), true) . 'Print.css', 'print');

        $this->set_parameter(ViewRenderer :: PARAM_TYPE, $this->getCurrentRendererType());
        $this->set_parameter(ViewRenderer :: PARAM_TIME, $this->getCurrentRendererTime());

        if ($this->getJumpForm()->validate())
        {
            $this->setCurrentRendererTime($this->getJumpForm()->getTime());
        }

//         $tabs = $this->getTabs();
//         $tabs->set_content($this->getCalendarHtml());

        $html = array();

        $html[] = $this->render_header();
        $html[] = $this->getCalendarHtml();
        // $html[] = $tabs->render();
        $html[] = $this->render_footer();

        return implode(PHP_EOL, $html);
    }

    public function getJumpForm()
    {
        if (! isset($this->jumpForm))
        {
            $this->jumpForm = new JumpForm($this->get_url(), $this->getCurrentRendererTime());
        }

        return $this->jumpForm;
    }

    /**
     *
     * @return string
     */
    public function getCalendarHtml()
    {
        $html = array();

        $html[] = '<div class="row">';
        $html[] = $this->renderNormalCalendar();
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

    protected function getCalendarDataProvider()
    {
        if (! isset($this->calendarDataProvider))
        {
            $displayParameters = array(
                self :: PARAM_CONTEXT => self :: package(),
                self :: PARAM_ACTION => self :: ACTION_BROWSE,
                ViewRenderer :: PARAM_TYPE => $this->getCurrentRendererType(),
                ViewRenderer :: PARAM_TIME => $this->getCurrentRendererTime());

            $this->calendarDataProvider = new CalendarRendererProvider(
                new CalendarRendererProviderRepository(),
                $this->get_user(),
                $this->get_user(),
                $displayParameters,
                \Chamilo\Application\Calendar\Ajax\Manager :: context());
        }

        return $this->calendarDataProvider;
    }

    protected function renderSidebar()
    {
        $dataProvider = $this->getCalendarDataProvider();
        $calendarLegend = new Legend($dataProvider);

        $mini_month_renderer = new MiniMonthRenderer(
            $dataProvider,
            $calendarLegend,
            $this->getCurrentRendererTime(),
            null,
            $this->getMiniMonthMarkPeriod());

        $html = array();

        $html[] = $mini_month_renderer->render();
        $html[] = $this->getJumpForm()->toHtml();

        return implode(PHP_EOL, $html);
    }

    protected function renderNormalCalendar()
    {
        $dataProvider = $this->getCalendarDataProvider();
        $calendarLegend = new Legend($dataProvider);

        $rendererFactory = new ViewRendererFactory(
            $this->getCurrentRendererType(),
            $dataProvider,
            $calendarLegend,
            $this->getCurrentRendererTime(),
            $this->getViewActions());
        $renderer = $rendererFactory->getRenderer();

        if ($this->getCurrentRendererType() == ViewRenderer :: TYPE_DAY ||
             $this->getCurrentRendererType() == ViewRenderer :: TYPE_WEEK)
        {
            $renderer->setStartHour(
                LocalSetting :: getInstance()->get('working_hours_start', 'Chamilo\Libraries\Calendar'));
            $renderer->setEndHour(LocalSetting :: getInstance()->get('working_hours_end', 'Chamilo\Libraries\Calendar'));
            $renderer->setHideOtherHours(
                LocalSetting :: getInstance()->get('hide_non_working_hours', 'Chamilo\Libraries\Calendar'));
        }

        return $renderer->render();
    }

    protected function getViewActions()
    {
        $actions = array();

        $actions[] = $this->getGeneralActions();

        $extensionRegistrations = Configuration :: registrations_by_type(
            \Chamilo\Application\Calendar\Manager :: package() . '\Extension');

        foreach ($extensionRegistrations as $extensionRegistration)
        {
            $actionRendererClass = $extensionRegistration[Registration :: PROPERTY_CONTEXT] . '\Actions';
            $actionRenderer = new $actionRendererClass();
            $extensionActions = $actionRenderer->get($this);

            foreach ($extensionActions as $extensionAction)
            {
                $actions[] = $extensionAction;
            }
        }

        return $actions;
    }

    protected function getGeneralActions()
    {
        $buttonGroup = new ButtonGroup();

        $printUrl = new Redirect(
            array(
                self :: PARAM_CONTEXT => self :: package(),
                self :: PARAM_ACTION => self :: ACTION_PRINT,
                ViewRenderer :: PARAM_TYPE => $this->getCurrentRendererType(),
                ViewRenderer :: PARAM_TIME => $this->getCurrentRendererTime()));

        $buttonGroup->addButton(
            new Button(
                Translation :: get(self :: ACTION_PRINT . 'Component'),
                Theme :: getInstance()->getImagePath(self :: package(), 'Tab/' . self :: ACTION_PRINT),
                $printUrl->getUrl(),
                Button :: DISPLAY_ICON));

        $iCalUrl = new Redirect(
            array(Application :: PARAM_CONTEXT => self :: package(), self :: PARAM_ACTION => Manager :: ACTION_ICAL));

        $buttonGroup->addButton(
            new Button(
                Translation :: get('ICalExternal'),
                Theme :: getInstance()->getImagePath(self :: package(), 'Tab/ICalExternal'),
                $iCalUrl->getUrl(),
                Button :: DISPLAY_ICON));

        $availabilityUrl = new Redirect(
            array(
                Application :: PARAM_CONTEXT => self :: package(),
                self :: PARAM_ACTION => Manager :: ACTION_AVAILABILITY));

        $buttonGroup->addButton(
            new Button(
                Translation :: get('AvailabilityComponent'),
                Theme :: getInstance()->getImagePath(self :: package(), 'Tab/Availability'),
                $availabilityUrl->getUrl(),
                Button :: DISPLAY_ICON));

        $settingsUrl = new Redirect(
            array(
                Application :: PARAM_CONTEXT => \Chamilo\Core\User\Manager :: context(),
                Application :: PARAM_ACTION => \Chamilo\Core\User\Manager :: ACTION_USER_SETTINGS,
                UserSettingsComponent :: PARAM_CONTEXT => 'Chamilo\Libraries\Calendar'));

        $buttonGroup->addButton(
            new Button(
                Translation :: get('ConfigComponent'),
                Theme :: getInstance()->getImagePath(self :: package(), 'Tab/Configuration'),
                $settingsUrl->getUrl(),
                Button :: DISPLAY_ICON));

        return $buttonGroup;
    }

    public function getMiniMonthMarkPeriod()
    {
        switch ($this->getCurrentRendererType())
        {
            case ViewRenderer :: TYPE_DAY :
                return MiniMonthCalendar :: PERIOD_DAY;
            case ViewRenderer :: TYPE_MONTH :
                return MiniMonthCalendar :: PERIOD_MONTH;
            case ViewRenderer :: TYPE_WEEK :
                return MiniMonthCalendar :: PERIOD_WEEK;
            default :
                return MiniMonthCalendar :: PERIOD_DAY;
        }
    }
}
