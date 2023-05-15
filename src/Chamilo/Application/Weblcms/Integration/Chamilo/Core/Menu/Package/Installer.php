<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Core\Menu\Package;

use Chamilo\Core\Menu\Storage\DataClass\Item;

/**
 * @package Chamilo\Application\Weblcms\Integration\Chamilo\Core\Menu\Package
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class Installer extends \Chamilo\Core\Menu\Action\Installer
{
    public const CONTEXT = 'Chamilo\Application\Weblcms\Integration\Chamilo\Core\Menu';

    /**
     * @param string[] $formValues
     */
    public function __construct($formValues)
    {
        parent::__construct($formValues, Item::DISPLAY_BOTH, false);
    }
}
