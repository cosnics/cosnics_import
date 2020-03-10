<?php
namespace Chamilo\Core\Home\Component;

use Chamilo\Core\Admin\Core\BreadcrumbGenerator;
use Chamilo\Core\Home\Manager;
use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Platform\Session\Session;

/**
 *
 * @package Chamilo\Core\Home\Component
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class PersonalComponent extends Manager
{

    private $user_id;

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        if ($this->get_user()->is_platform_admin())
        {
            Session::unregister('Chamilo\Core\Home\General');
        }
        
        $redirect = new Redirect();
        $redirect->toUrl();
    }

    /**
     * Returns the admin breadcrumb generator
     * 
     * @return \libraries\format\BreadcrumbGeneratorInterface
     */
    public function get_breadcrumb_generator()
    {
        return new BreadcrumbGenerator($this, BreadcrumbTrail::getInstance());
    }
}
