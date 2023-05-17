<?php
namespace Chamilo\Libraries\Calendar\Architecture\Factory;

use Chamilo\Libraries\Architecture\Traits\DependencyInjectionContainerTrait;
use Chamilo\Libraries\Calendar\Service\View\HtmlCalendarRenderer;

/**
 * @package Chamilo\Libraries\Calendar\Architecture\Factory
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class HtmlCalendarRendererFactory
{
    use DependencyInjectionContainerTrait;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->initializeContainer();
    }

    /**
     * @throws \Exception
     */
    public function getRenderer(string $rendererType): HtmlCalendarRenderer
    {
        $className = 'Chamilo\Libraries\Calendar\Service\View\\' . $rendererType . 'CalendarRenderer';

        return $this->getService($className);
    }
}