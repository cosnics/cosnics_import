<?php
namespace Chamilo\Core\Repository\ContentObject\Webpage\DependencyInjection;

use Chamilo\Libraries\DependencyInjection\AbstractDependencyInjectionExtension;
use Chamilo\Libraries\DependencyInjection\Traits\ExtensionTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * Extension on the dependency injection container.
 * Loads local services and parameters for this package.
 *
 * @see     http://symfony.com/doc/current/components/dependency_injection/compilation.html
 * @package Chamilo\Core\Repository\ContentObject\Webpage\DependencyInjection
 * @author  Sven Vanpoucke - Hogeschool Gent
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class DependencyInjectionExtension extends AbstractDependencyInjectionExtension implements ExtensionInterface
{
    use ExtensionTrait;

    public function getAlias(): string
    {
        return 'chamilo.core.repository.content_object.webpage';
    }

    public function getConfigurationFiles(): array
    {
        return [
            'Chamilo\Core\Repository\ContentObject\Webpage' => [
                'package.xml',
                'tables.xml'
            ]
        ];
    }
}