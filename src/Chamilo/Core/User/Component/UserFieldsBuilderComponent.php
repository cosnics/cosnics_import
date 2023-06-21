<?php
namespace Chamilo\Core\User\Component;

use Chamilo\Core\User\Manager;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;

/**
 *
 * @package user.lib.user_manager.component
 */
class UserFieldsBuilderComponent extends Manager
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $this->checkAuthorization(Manager::CONTEXT, 'ManageUserFields');

        if (! $this->get_user()->isPlatformAdmin())
        {
            throw new NotAllowedException();
        }

        $application = $this->getApplicationFactory()->getApplication(
            \Chamilo\Configuration\Form\Manager::CONTEXT,
            new ApplicationConfiguration($this->getRequest(), $this->get_user(), $this));
        $application->set_form_by_name('account_fields');

        return $application->run();
    }
}
