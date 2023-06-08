<?php
namespace Chamilo\Core\Home\Rights;

use Chamilo\Core\Admin\Core\BreadcrumbGenerator;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Format\Structure\BreadcrumbGeneratorInterface;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;

/**
 * Manager for the components
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
abstract class Manager extends Application
{
    public const ACTION_BROWSE_BLOCK_TYPE_TARGET_ENTITIES = 'BrowseBlockTypeTargetEntities';
    public const ACTION_SET_BLOCK_TYPE_TARGET_ENTITIES = 'SetBlockTypeTargetEntities';

    public const CONTEXT = __NAMESPACE__;
    public const DEFAULT_ACTION = self::ACTION_BROWSE_BLOCK_TYPE_TARGET_ENTITIES;

    public const PARAM_BLOCK_TYPE = 'block_type';

    public function get_breadcrumb_generator(): BreadcrumbGeneratorInterface
    {
        return new BreadcrumbGenerator($this, BreadcrumbTrail::getInstance());
    }
}
