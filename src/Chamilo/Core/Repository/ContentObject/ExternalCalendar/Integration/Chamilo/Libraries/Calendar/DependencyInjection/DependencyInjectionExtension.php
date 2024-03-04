<?php
namespace Chamilo\Core\Repository\ContentObject\ExternalCalendar\Integration\Chamilo\Libraries\Calendar\DependencyInjection;

use Chamilo\Libraries\DependencyInjection\AbstractDependencyInjectionExtension;
use Chamilo\Libraries\DependencyInjection\Traits\ExtensionTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * @package Chamilo\Core\Repository\ContentObject\ExternalCalendar\Integration\Chamilo\Libraries\Calendar\DependencyInjection
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class DependencyInjectionExtension extends AbstractDependencyInjectionExtension implements ExtensionInterface
{
    use ExtensionTrait;

    public function getAlias(): string
    {
        return 'chamilo.core.repository.contentobject.externalcalendar.integration.chamilo.libraries.calendar';
    }

    public function getConfigurationFiles(): array
    {
        return ['Chamilo\Core\Repository\ContentObject\ExternalCalendar\Integration\Chamilo\Libraries\Calendar' => ['package.xml']];
    }
}