<?php
namespace Chamilo\Core\Menu\Renderer;

use Chamilo\Configuration\Service\ConfigurationConsulter;
use Chamilo\Core\Menu\Factory\ItemRendererFactory;
use Chamilo\Core\Menu\Service\ItemCacheService;
use Chamilo\Core\Menu\Service\RightsCacheService;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\File\PathBuilder;
use Chamilo\Libraries\Format\Theme\ThemePathBuilder;
use Chamilo\Libraries\Platform\ChamiloRequest;

/**
 * @package Chamilo\Core\Menu\Renderer
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class MenuRenderer
{
    /**
     * @var \Chamilo\Core\Menu\Service\ItemCacheService
     */
    private $itemCacheService;

    /**
     * @var \Chamilo\Core\Menu\Service\RightsCacheService
     */
    private $rightsCacheService;

    /**
     * @var \Chamilo\Core\Menu\Factory\ItemRendererFactory
     */
    private $itemRendererFactory;

    /**
     * @var \Chamilo\Libraries\Platform\ChamiloRequest
     */
    private $chamiloRequest;

    /**
     * @var \Chamilo\Configuration\Service\ConfigurationConsulter
     */
    private $configurationConsulter;

    /**
     * @var \Chamilo\Libraries\File\PathBuilder
     */
    private $pathBuilder;

    /**
     * @var \Chamilo\Libraries\Format\Theme\ThemePathBuilder
     */
    private $themePathBuilder;

    /**
     * @param \Chamilo\Core\Menu\Service\ItemCacheService $itemCacheService
     * @param \Chamilo\Core\Menu\Service\RightsCacheService $rightsCacheService
     * @param \Chamilo\Core\Menu\Factory\ItemRendererFactory $itemRendererFactory
     * @param \Chamilo\Libraries\Platform\ChamiloRequest $chamiloRequest
     * @param \Chamilo\Configuration\Service\ConfigurationConsulter $configurationConsulter
     * @param \Chamilo\Libraries\File\PathBuilder $pathBuilder
     * @param \Chamilo\Libraries\Format\Theme\ThemePathBuilder $themePathBuilder
     */
    public function __construct(
        ItemCacheService $itemCacheService, RightsCacheService $rightsCacheService,
        ItemRendererFactory $itemRendererFactory, ChamiloRequest $chamiloRequest,
        ConfigurationConsulter $configurationConsulter, PathBuilder $pathBuilder, ThemePathBuilder $themePathBuilder
    )
    {
        $this->itemCacheService = $itemCacheService;
        $this->rightsCacheService = $rightsCacheService;
        $this->itemRendererFactory = $itemRendererFactory;
        $this->chamiloRequest = $chamiloRequest;
        $this->configurationConsulter = $configurationConsulter;
        $this->pathBuilder = $pathBuilder;
        $this->themePathBuilder = $themePathBuilder;
    }

    /**
     * @param string $containerMode
     * @param \Chamilo\Core\User\Storage\DataClass\User $user
     *
     * @return string
     */
    public function render(string $containerMode = 'container-fluid', User $user = null)
    {
        $html = array();

        $numberOfItems = 0;
        $itemRenditions = array();

        if ($user instanceof User)
        {
            foreach ($this->findRootItems() as $item)
            {
                $userCanViewItem = $this->getRightsCacheService()->canUserViewItem($user, $item);

                if ($userCanViewItem)
                {
                    if (!$item->isHidden())
                    {
                        $itemRenderer = $this->getItemRendererFactory()->getItemRenderer($item);
                        $itemHtml = $itemRenderer->render($item, $user);

                        if (!empty($itemHtml))
                        {
                            $numberOfItems ++;
                            $itemRenditions[] = $itemHtml;
                        }
                    }
                }
            }
        }

        $html[] = $this->renderHeader($containerMode, $numberOfItems);
        $html[] = implode(PHP_EOL, $itemRenditions);
        $html[] = $this->renderFooter();

        return implode(PHP_EOL, $html);
    }

    /**
     * @return \Chamilo\Core\Menu\Storage\DataClass\Item[]
     */
    public function findRootItems()
    {
        return $this->getItemCacheService()->findItemsByParentIdentifier(0);
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
     * @return \Chamilo\Configuration\Service\ConfigurationConsulter
     */
    public function getConfigurationConsulter(): ConfigurationConsulter
    {
        return $this->configurationConsulter;
    }

    /**
     * @param \Chamilo\Configuration\Service\ConfigurationConsulter $configurationConsulter
     */
    public function setConfigurationConsulter(ConfigurationConsulter $configurationConsulter): void
    {
        $this->configurationConsulter = $configurationConsulter;
    }

    /**
     * @return \Chamilo\Core\Menu\Service\ItemCacheService
     */
    public function getItemCacheService(): ItemCacheService
    {
        return $this->itemCacheService;
    }

    /**
     * @param \Chamilo\Core\Menu\Service\ItemCacheService $itemCacheService
     */
    public function setItemCacheService(ItemCacheService $itemCacheService): void
    {
        $this->itemCacheService = $itemCacheService;
    }

    /**
     * @return \Chamilo\Core\Menu\Factory\ItemRendererFactory
     */
    public function getItemRendererFactory(): ItemRendererFactory
    {
        return $this->itemRendererFactory;
    }

    /**
     * @param \Chamilo\Core\Menu\Factory\ItemRendererFactory $itemRendererFactory
     */
    public function setItemRendererFactory(ItemRendererFactory $itemRendererFactory): void
    {
        $this->itemRendererFactory = $itemRendererFactory;
    }

    /**
     * @return \Chamilo\Libraries\File\PathBuilder
     */
    public function getPathBuilder(): PathBuilder
    {
        return $this->pathBuilder;
    }

    /**
     * @param \Chamilo\Libraries\File\PathBuilder $pathBuilder
     */
    public function setPathBuilder(PathBuilder $pathBuilder): void
    {
        $this->pathBuilder = $pathBuilder;
    }

    /**
     * @return \Chamilo\Core\Menu\Service\RightsCacheService
     */
    public function getRightsCacheService(): RightsCacheService
    {
        return $this->rightsCacheService;
    }

    /**
     * @param \Chamilo\Core\Menu\Service\RightsCacheService $rightsCacheService
     */
    public function setRightsCacheService(RightsCacheService $rightsCacheService): void
    {
        $this->rightsCacheService = $rightsCacheService;
    }

    /**
     * @return \Chamilo\Libraries\Format\Theme\ThemePathBuilder
     */
    public function getThemePathBuilder(): ThemePathBuilder
    {
        return $this->themePathBuilder;
    }

    /**
     * @param \Chamilo\Libraries\Format\Theme\ThemePathBuilder $themePathBuilder
     */
    public function setThemePathBuilder(ThemePathBuilder $themePathBuilder): void
    {
        $this->themePathBuilder = $themePathBuilder;
    }

    /**
     * @return string
     */
    public function renderBrand()
    {
        $configurationConsulter = $this->getConfigurationConsulter();

        $siteName = $configurationConsulter->getSetting(array('Chamilo\Core\Admin', 'site_name'));
        $brandImage = $configurationConsulter->getSetting(array('Chamilo\Core\Menu', 'brand_image'));

        if ($brandImage)
        {
            $brandSource = $brandImage;
        }
        else
        {
            $brandSource = $this->getThemePathBuilder()->getImagePath('Chamilo\Configuration', 'LogoHeader');
        }

        $basePath = $this->getPathBuilder()->getBasePath(true);

        return '<a class="navbar-brand" href="' . $basePath . '">' . '<img alt="' . $siteName . '" src="' .
            $brandSource . '"></a>';
    }

    /**
     * @return string
     */
    public function renderFooter()
    {
        $html = array();

        $html[] = '</ul>';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '</nav>';

        return implode(PHP_EOL, $html);
    }

    /**
     * @param string $containerMode
     * @param integer $numberOfItems
     *
     * @return string
     */
    public function renderHeader(string $containerMode, int $numberOfItems = 0)
    {
        $html = array();

        $class = 'navbar navbar-chamilo navbar-inverse';

        if ($numberOfItems == 0)
        {
            $class .= ' navbar-no-items';
        }

        $html[] = '<nav class="' . $class . '">';
        $html[] = '<div class="' . $containerMode . '">';
        $html[] = '<div class="navbar-header">';

        $html[] =
            '<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#menu-navbar-collapse" aria-expanded="false">';
        $html[] = '<span class="sr-only">Toggle navigation</span>';
        $html[] = '<span class="icon-bar"></span>';
        $html[] = '<span class="icon-bar"></span>';
        $html[] = '<span class="icon-bar"></span>';
        $html[] = '</button>';
        $html[] = $this->renderBrand();

        $html[] = '</div>';
        $html[] = '<div class="collapse navbar-collapse" id="menu-navbar-collapse">';
        $html[] = '<ul class="nav navbar-nav navbar-right">';

        return implode(PHP_EOL, $html);
    }

}