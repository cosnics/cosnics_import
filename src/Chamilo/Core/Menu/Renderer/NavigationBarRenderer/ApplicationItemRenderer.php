<?php
namespace Chamilo\Core\Menu\Renderer\NavigationBarRenderer;

use Chamilo\Configuration\Service\RegistrationConsulter;
use Chamilo\Core\Menu\Service\ItemService;
use Chamilo\Core\Menu\Storage\DataClass\ApplicationItem;
use Chamilo\Core\Menu\Storage\DataClass\Item;
use Chamilo\Core\Rights\Structure\Service\Interfaces\AuthorizationCheckerInterface;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\ChamiloRequest;
use Symfony\Component\Translation\Translator;

/**
 * @package Chamilo\Core\Menu\Renderer\NavigationBarRenderer
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class ApplicationItemRenderer extends NavigationBarItemRenderer
{
    /**
     * @var \Chamilo\Core\Rights\Structure\Service\Interfaces\AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var \Chamilo\Configuration\Service\RegistrationConsulter
     */
    private $registrationConsulter;

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    private $translator;

    /**
     * @var \Chamilo\Core\Menu\Service\ItemService
     */
    private $itemService;

    /**
     * @var \Chamilo\Libraries\Format\Theme
     */
    private $themeUtilities;

    /**
     * @var \Chamilo\Libraries\Platform\ChamiloRequest
     */
    private $chamiloRequest;

    /**
     * @param \Chamilo\Core\Rights\Structure\Service\Interfaces\AuthorizationCheckerInterface $authorizationChecker
     * @param \Chamilo\Configuration\Service\RegistrationConsulter $registrationConsulter
     * @param \Symfony\Component\Translation\Translator $translator
     * @param \Chamilo\Core\Menu\Service\ItemService $itemService
     * @param \Chamilo\Libraries\Format\Theme $themeUtilities
     * @param \Chamilo\Libraries\Platform\ChamiloRequest $chamiloRequest
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker, RegistrationConsulter $registrationConsulter,
        Translator $translator, ItemService $itemService, Theme $themeUtilities, ChamiloRequest $chamiloRequest
    )
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->registrationConsulter = $registrationConsulter;
        $this->translator = $translator;
        $this->itemService = $itemService;
        $this->themeUtilities = $themeUtilities;
        $this->chamiloRequest = $chamiloRequest;
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
     * @return \Chamilo\Configuration\Service\RegistrationConsulter
     */
    public function getRegistrationConsulter(): RegistrationConsulter
    {
        return $this->registrationConsulter;
    }

    /**
     * @param \Chamilo\Configuration\Service\RegistrationConsulter $registrationConsulter
     */
    public function setRegistrationConsulter(RegistrationConsulter $registrationConsulter): void
    {
        $this->registrationConsulter = $registrationConsulter;
    }

    /**
     * @return \Symfony\Component\Translation\Translator
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * @param \Symfony\Component\Translation\Translator $translator
     */
    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @return \Chamilo\Core\Menu\Service\ItemService
     */
    public function getItemService(): ItemService
    {
        return $this->itemService;
    }

    /**
     * @param \Chamilo\Core\Menu\Service\ItemService $itemService
     */
    public function setItemService(ItemService $itemService): void
    {
        $this->itemService = $itemService;
    }

    /**
     * @return \Chamilo\Libraries\Format\Theme
     */
    public function getThemeUtilities(): Theme
    {
        return $this->themeUtilities;
    }

    /**
     * @param \Chamilo\Libraries\Format\Theme $themeUtilities
     */
    public function setThemeUtilities(Theme $themeUtilities): void
    {
        $this->themeUtilities = $themeUtilities;
    }

    /**
     * @return \Chamilo\Libraries\Platform\ChamiloRequest
     */
    public function getChamiloRequest(): ChamiloRequest
    {
        return $this->chamiloRequest;
    }

    /**
     * @param \Chamilo\Libraries\Platform\ChamiloRequest $chamiloRequest
     */
    public function setChamiloRequest(ChamiloRequest $chamiloRequest): void
    {
        $this->chamiloRequest = $chamiloRequest;
    }

    /**
     * @param \Chamilo\Core\Menu\Storage\DataClass\ApplicationItem $item
     * @param \Chamilo\Core\User\Storage\DataClass\User $user
     *
     * @return boolean
     */
    public function isItemVisibleForUser(ApplicationItem $item, User $user)
    {
        $isAuthorized = $this->getAuthorizationChecker()->isAuthorized($user, $item->getApplication());
        $isActiveApplication = $this->getRegistrationConsulter()->isContextRegisteredAndActive($item->getApplication());

        return $isAuthorized && $isActiveApplication;
    }

    /**
     * @param \Chamilo\Core\Menu\Storage\DataClass\ApplicationItem $item
     *
     * @return boolean
     */
    public function isSelected(Item $item)
    {
        $request = $this->getChamiloRequest();

        $currentContext = $request->query->get(Application::PARAM_CONTEXT);
        $currentAction = $request->query->get(Application::PARAM_ACTION);

        if ($currentContext != $item->getApplication())
        {
            return false;
        }

        if ($item->getComponent() && $currentAction != $item->getComponent())
        {
            return false;
        }

        return true;
    }

    /**
     * @param \Chamilo\Core\Menu\Storage\DataClass\ApplicationItem $item
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

        $translator = $this->getTranslator();

        $url = $this->getApplicationItemUrl($item);

        $html = array();

        $isSelected = $this->isSelected($item);

        $html[] = '<li' . ($isSelected ? ' class="active"' : '') . '>';

        if ($item->getUseTranslation())
        {
            $title = $translator->trans('TypeName', [], $item->getApplication());
        }
        else
        {
            $title = $this->getItemService()->getItemTitleForCurrentLanguage($item);
        }

        $html[] = '<a href="' . $url . '">';

        if ($item->showIcon())
        {
            if (!empty($item->getIconClass()))
            {
                $html[] = $this->renderCssIcon($item);
            }
            else
            {
                $integrationNamespace = $item->getApplication() . '\Integration\Chamilo\Core\Menu';
                $imagePath = $this->getThemeUtilities()->getImagePath(
                    $integrationNamespace, 'Menu' . ($isSelected ? 'Selected' : '')
                );

                $html[] = '<img class="chamilo-menu-item-icon' .
                    ($item->showTitle() ? ' chamilo-menu-item-image-with-label' : '') . '" src="' . $imagePath .
                    '" title="' . htmlentities($title) . '" alt="' . $title . '" />';
            }
        }

        if ($item->showTitle())
        {
            $html[] = '<div class="chamilo-menu-item-label' .
                ($item->showIcon() ? ' chamilo-menu-item-label-with-image' : '') . '">' . $title . '</div>';
        }

        $html[] = '<div class="clearfix"></div>';
        $html[] = '</a>';
        $html[] = '</li>';

        return implode(PHP_EOL, $html);
    }

    /**
     *
     * @param \Chamilo\Core\Menu\Storage\DataClass\ApplicationItem $item
     *
     * @return string
     */
    protected function getApplicationItemUrl(ApplicationItem $item)
    {
        $url = new Redirect();

        if ($item->getApplication() == 'root')
        {
            return $url->getUrl();
        }

        $url->setParameter(Application::PARAM_CONTEXT, $item->getApplication());

        if ($item->getComponent())
        {
            $url->setParameter(Application::PARAM_ACTION, $item->getComponent());
        }

        if ($item->getExtraParameters())
        {
            $extraParameters = parse_str($item->getExtraParameters());

            foreach ($extraParameters as $key => $value)
            {
                $url->setParameter($key, $value);
            }
        }

        return $url->getUrl();
    }
}