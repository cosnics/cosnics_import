<?php
namespace Chamilo\Core\User\Component;

use Chamilo\Configuration\Configuration;
use Chamilo\Core\User\Form\RegisterForm;
use Chamilo\Core\User\Manager;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Architecture\Interfaces\NoAuthenticationSupport;
use Chamilo\Libraries\Format\Structure\BreadcrumbGenerator;
use Chamilo\Libraries\Format\Structure\BreadcrumbGeneratorInterface;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Translation\Translation;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @package user.lib.user_manager.component
 */
class RegisterComponent extends Manager implements NoAuthenticationSupport
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $allow_registration = Configuration::getInstance()->get_setting([Manager::CONTEXT, 'allow_registration']);
        if (!$allow_registration)
        {
            throw new NotAllowedException();
        }

        $user = new User();
        $user->set_platformadmin(0);
        $user->set_password(1);
        // $user->set_creator_id($user_info['user_id']);

        $form = new RegisterForm($user, $this->get_url());

        if ($form->validate())
        {
            $success = $form->create_user();
            if ($success == 1)
            {
                $parameters = [];

                if (Configuration::getInstance()->get_setting([Manager::CONTEXT, 'allow_registration']) == 2)
                {
                    $parameters['message'] = Translation::get('UserAwaitingApproval');
                }

                $parameters[Application::PARAM_CONTEXT] = '';

                return new RedirectResponse($this->getUrlGenerator()->fromParameters($parameters));
            }
            else
            {
                Request::set_get('error_message', Translation::get('UsernameNotAvailable'));

                $html = [];

                $html[] = $this->render_header();
                $html[] = $form->toHtml();
                $html[] = $this->render_footer();

                return implode(PHP_EOL, $html);
            }
        }
        else
        {
            $html = [];

            $html[] = $this->render_header();
            $html[] = $form->toHtml();
            $html[] = $this->render_footer();

            return implode(PHP_EOL, $html);
        }
    }

    public function get_breadcrumb_generator(): BreadcrumbGeneratorInterface
    {
        return new BreadcrumbGenerator($this, BreadcrumbTrail::getInstance());
    }
}
