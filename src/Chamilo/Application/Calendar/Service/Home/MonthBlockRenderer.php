<?php
namespace Chamilo\Application\Calendar\Service\Home;

use Chamilo\Application\Calendar\Ajax\Manager;
use Chamilo\Application\Calendar\Repository\CalendarRendererProviderRepository;
use Chamilo\Application\Calendar\Service\CalendarRendererProvider;
use Chamilo\Configuration\Service\Consulter\ConfigurationConsulter;
use Chamilo\Core\Home\Architecture\Interfaces\StaticBlockTitleInterface;
use Chamilo\Core\Home\Form\ConfigurationFormFactory;
use Chamilo\Core\Home\Renderer\BlockRenderer;
use Chamilo\Core\Home\Rights\Service\ElementRightsService;
use Chamilo\Core\Home\Service\HomeService;
use Chamilo\Core\Home\Storage\DataClass\Element;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Application\Routing\UrlGenerator;
use Chamilo\Libraries\Calendar\Service\View\HtmlCalendarRenderer;
use Chamilo\Libraries\Calendar\Service\View\MiniMonthCalendarRenderer;
use Chamilo\Libraries\Platform\ChamiloRequest;
use Chamilo\Libraries\Utilities\StringUtilities;
use Symfony\Component\Translation\Translator;

/**
 * @package Chamilo\Application\Calendar\Service\Home
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class MonthBlockRenderer extends BlockRenderer implements StaticBlockTitleInterface
{
    public const CONTEXT = \Chamilo\Application\Calendar\Manager::CONTEXT;

    protected MiniMonthCalendarRenderer $miniMonthCalendarRenderer;

    protected ChamiloRequest $request;

    public function __construct(
        HomeService $homeService, UrlGenerator $urlGenerator, Translator $translator,
        ConfigurationConsulter $configurationConsulter, MiniMonthCalendarRenderer $miniMonthCalendarRenderer,
        ChamiloRequest $request, ElementRightsService $elementRightsService,
        ConfigurationFormFactory $configurationFormFactory
    )
    {
        parent::__construct(
            $homeService, $urlGenerator, $translator, $configurationConsulter, $elementRightsService,
            $configurationFormFactory
        );

        $this->miniMonthCalendarRenderer = $miniMonthCalendarRenderer;
        $this->request = $request;
    }

    /**
     * @throws \Exception
     */
    public function displayContent(Element $block, ?User $user = null): string
    {
        $dataProvider = new CalendarRendererProvider(
            new CalendarRendererProviderRepository(), $user, [
            Application::PARAM_CONTEXT => \Chamilo\Application\Calendar\Manager::CONTEXT,
            HtmlCalendarRenderer::PARAM_TYPE => HtmlCalendarRenderer::TYPE_DAY
        ], Manager::CONTEXT
        );

        return $this->getMiniMonthCalendarRenderer()->renderCalendar($dataProvider, $this->getDisplayTime());
    }

    protected function getDisplayTime(): int
    {
        return (int) $this->getRequest()->query->get('time', time());
    }

    public function getMiniMonthCalendarRenderer(): MiniMonthCalendarRenderer
    {
        return $this->miniMonthCalendarRenderer;
    }

    public function getRequest(): ChamiloRequest
    {
        return $this->request;
    }

    public function getTitle(Element $block, ?User $user = null): string
    {
        return $this->getTranslator()->trans(date('F', $this->getDisplayTime()) . 'Long', [], StringUtilities::LIBRARIES
            ) . ' ' . date('Y', $this->getDisplayTime());
    }

    public function renderContentFooter(Element $block): string
    {
        return '</div>';
    }

    public function renderContentHeader(Element $block): string
    {
        return '<div class="portal-block-content' . ($block->isVisible() ? '' : ' hidden') . '">';
    }
}
