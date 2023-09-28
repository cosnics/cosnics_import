<?php
namespace Chamilo\Libraries\Authentication;

use Chamilo\Configuration\Service\Consulter\ConfigurationConsulter;
use Chamilo\Core\User\Service\UserService;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Platform\ChamiloRequest;
use Chamilo\Libraries\Utilities\StringUtilities;
use Symfony\Component\Translation\Translator;

/**
 * @package Chamilo\Libraries\Authentication
 * @author  Sven Vanpoucke - Hogeschool Gent
 */
abstract class Authentication implements AuthenticationInterface
{
    public const PARAM_LOGIN = 'login';
    public const PARAM_PASSWORD = 'password';

    protected ConfigurationConsulter $configurationConsulter;

    protected ChamiloRequest $request;

    protected Translator $translator;

    protected UserService $userService;

    public function __construct(
        ConfigurationConsulter $configurationConsulter, Translator $translator, ChamiloRequest $request,
        UserService $userService
    )
    {
        $this->configurationConsulter = $configurationConsulter;
        $this->translator = $translator;
        $this->request = $request;
        $this->userService = $userService;
    }

    public function getConfigurationConsulter(): ConfigurationConsulter
    {
        return $this->configurationConsulter;
    }

    public function getRequest(): ChamiloRequest
    {
        return $this->request;
    }

    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * @throws \Chamilo\Libraries\Authentication\AuthenticationException
     */
    protected function getUserFromCredentialsRequest(): ?User
    {
        $username = $this->getRequest()->request->get(self::PARAM_LOGIN);

        if (empty($username))
        {
            return null;
        }

        $user = $this->getUserService()->getUserByUsernameOrEmail($username);
        $translator = $this->getTranslator();

        if (!$user instanceof User)
        {
            throw new AuthenticationException(
                $translator->trans('InvalidUsername', [], StringUtilities::LIBRARIES)
            );
        }

        if ($user->getAuthenticationSource() != $this->getAuthenticationType())
        {
            return null;
        }

        if (!$this->isAuthSourceActive())
        {
            throw new AuthenticationException(
                $translator->trans('AuthSourceNotActive', [], StringUtilities::LIBRARIES)
            );
        }

        return $user;
    }

    public function getUserService(): UserService
    {
        return $this->userService;
    }

    protected function isAuthSourceActive(): bool
    {
        return (bool) $this->getConfigurationConsulter()->getSetting(
            ['Chamilo\Core\Admin', 'enable' . str_replace('\\', '', $this->getAuthenticationType())]
        );
    }

}
