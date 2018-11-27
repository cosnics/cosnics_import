<?php
namespace Chamilo\Core\Menu\Renderer\NavigationBarRenderer;

use Chamilo\Core\Menu\Renderer\Item\Bar\Item\LinkItem;
use Chamilo\Core\Menu\Storage\DataClass\Item;
use Chamilo\Core\Rights\Structure\Service\Interfaces\AuthorizationCheckerInterface;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\File\Redirect;

/**
 * @package Chamilo\Core\Menu\Renderer\NavigationBarRenderer
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class LanguageItemRenderer extends NavigationBarItemRenderer
{
    /**
     * @var \Chamilo\Core\Rights\Structure\Service\Interfaces\AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @param \Chamilo\Core\Rights\Structure\Service\Interfaces\AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker
    )
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @return \Chamilo\Core\Rights\Structure\Service\Interfaces\AuthorizationCheckerInterface
     */
    public function getAuthorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->authorizationChecker;
    }

    /**
     * @param \Chamilo\Core\Rights\Structure\Service\Interfaces\AuthorizationCheckerInterface $authorizationChecker
     */
    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker): void
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param \Chamilo\Core\Menu\Storage\DataClass\LinkItem $item
     * @param \Chamilo\Core\User\Storage\DataClass\User $user
     *
     * @return boolean
     */
    public function isItemVisibleForUser(LinkItem $item, User $user)
    {
        return $this->getAuthorizationChecker()->isAuthorized($user, 'Chamilo\Core\User', 'ChangeLanguage');
    }

    /**
     * @param \Chamilo\Core\Menu\Storage\DataClass\LanguageItem $item
     * @param \Chamilo\Core\User\Storage\DataClass\User $user
     *
     * @return string
     */
    public function render(Item $item, User $user)
    {
        if (!$this->isItemVisibleForUser($item, $user))
        {
            return '';
        }

        $redirect = new Redirect(
            array(
                Application::PARAM_CONTEXT => \Chamilo\Core\User\Manager::context(),
                Application::PARAM_ACTION => \Chamilo\Core\User\Manager::ACTION_QUICK_LANG,
                \Chamilo\Core\User\Manager::PARAM_CHOICE => $item->getLanguage(),
                \Chamilo\Core\User\Manager::PARAM_REFER => $item->getCurrentUrl()
            )
        );

        $html[] = array();

        $html[] = '<li>';
        $html[] = '<a href="' . $redirect->getUrl() . '">';
        $html[] = '<div class="chamilo-menu-item-label">';
        $html[] = $item->getLanguage();
        $html[] = '</div>';
        $html[] = '</a>';
        $html[] = '</li>';

        return implode(PHP_EOL, $html);
    }
}