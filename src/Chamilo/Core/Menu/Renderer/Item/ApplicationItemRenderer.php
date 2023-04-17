<?php
namespace Chamilo\Core\Menu\Renderer\Item;

use Chamilo\Configuration\Service\Consulter\RegistrationConsulter;
use Chamilo\Core\Menu\Renderer\ItemRenderer;
use Chamilo\Core\Menu\Service\ItemCacheService;
use Chamilo\Core\Menu\Storage\DataClass\ApplicationItem;
use Chamilo\Core\Menu\Storage\DataClass\Item;
use Chamilo\Core\Rights\Structure\Service\Interfaces\AuthorizationCheckerInterface;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Application\Routing\UrlGenerator;
use Chamilo\Libraries\Format\Structure\Glyph\IdentGlyph;
use Chamilo\Libraries\Format\Structure\Glyph\NamespaceIdentGlyph;
use Chamilo\Libraries\Format\Theme\ThemePathBuilder;
use Chamilo\Libraries\Platform\ChamiloRequest;
use Symfony\Component\Translation\Translator;

/**
 * @package Chamilo\Core\Menu\Renderer\ItemRenderer
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class ApplicationItemRenderer extends ItemRenderer
{
    private RegistrationConsulter $registrationConsulter;

    private UrlGenerator $urlGenerator;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker, Translator $translator, ItemCacheService $itemCacheService,
        ThemePathBuilder $themePathBuilder, ChamiloRequest $request, RegistrationConsulter $registrationConsulter,
        UrlGenerator $urlGenerator
    )
    {
        parent::__construct($authorizationChecker, $translator, $itemCacheService, $themePathBuilder, $request);

        $this->registrationConsulter = $registrationConsulter;
        $this->urlGenerator = $urlGenerator;
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

        $url = $this->getApplicationItemUrl($item);

        $html = [];

        $isSelected = $this->isSelected($item);

        $html[] = '<li class="' . implode(' ', $this->getClasses($isSelected)) . '">';

        $title = $this->renderTitle($item);

        $html[] = '<a href="' . $url . '">';

        if ($item->showIcon())
        {
            if (!empty($item->getIconClass()))
            {
                $html[] = $this->renderCssIcon($item);
            }
            else
            {
                $glyph = new NamespaceIdentGlyph(
                    $item->getApplication(), false, false, false, IdentGlyph::SIZE_MEDIUM, [], $title
                );

                $html[] = $glyph->render();
            }
        }

        if ($item->showTitle())
        {
            $html[] = '<div>' . $title . '</div>';
        }

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
        if ($item->getApplication() == 'root')
        {
            return $this->getUrlGenerator()->fromParameters();
        }

        $parameters = [];

        $parameters[Application::PARAM_CONTEXT] = $item->getApplication();

        if ($item->getComponent())
        {
            $parameters[Application::PARAM_ACTION] = $item->getComponent();
        }

        if ($item->getExtraParameters())
        {
            parse_str($item->getExtraParameters(), $extraParameters);

            foreach ($extraParameters as $key => $value)
            {
                $parameters[$key] = $value;
            }
        }

        return $this->getUrlGenerator()->fromParameters($parameters);
    }

    /**
     * @return \Chamilo\Configuration\Service\Consulter\RegistrationConsulter
     */
    public function getRegistrationConsulter(): RegistrationConsulter
    {
        return $this->registrationConsulter;
    }

    /**
     * @param \Chamilo\Configuration\Service\Consulter\RegistrationConsulter $registrationConsulter
     */
    public function setRegistrationConsulter(RegistrationConsulter $registrationConsulter): void
    {
        $this->registrationConsulter = $registrationConsulter;
    }

    public function getUrlGenerator(): UrlGenerator
    {
        return $this->urlGenerator;
    }

    /**
     * @param \Chamilo\Core\Menu\Storage\DataClass\ApplicationItem $item
     * @param \Chamilo\Core\User\Storage\DataClass\User $user
     *
     * @return bool
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
     * @return bool
     */
    public function isSelected(Item $item)
    {
        $request = $this->getRequest();

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
     *
     * @return string
     */
    public function renderTitle(Item $item)
    {
        if ($item->getUseTranslation())
        {
            return $this->getTranslator()->trans('TypeName', [], $item->getApplication());
        }

        return parent::renderTitle($item);
    }
}