<?php
namespace Chamilo\Libraries\Protocol\Microsoft\Graph;

use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Protocol\Microsoft\Graph\Service\UserService;

/**
 *
 * @package Chamilo\Libraries\Protocol\Microsoft\Graph
 * @author Sven Vanpoucke - Hogeschool Gent
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
abstract class Manager extends Application
{
    const ACTION_AUTHENTICATE = 'Authentication';

    const DEFAULT_ACTION = self::ACTION_AUTHENTICATE;

    const PARAM_ACTION = 'GraphAction';

    /**
     *
     * @return \Chamilo\Libraries\Protocol\Microsoft\Graph\Service\UserService
     */
    protected function getGraphService()
    {
        return $this->getService(UserService::class);
    }
}