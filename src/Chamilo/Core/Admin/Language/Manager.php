<?php
namespace Chamilo\Core\Admin\Language;

use Chamilo\Libraries\Architecture\Application\Application;

/**
 *
 * @package admin.lib.package_manager
 * @author Hans De Bisschop
 */
abstract class Manager extends Application
{
    public const ACTION_EXPORT = 'Exporter';
    public const ACTION_IMPORT = 'Importer';

    public const DEFAULT_ACTION = self::ACTION_IMPORT;

    public const PARAM_ACTION = 'language_action';
}
