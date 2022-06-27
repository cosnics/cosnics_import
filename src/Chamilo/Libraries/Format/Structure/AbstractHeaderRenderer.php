<?php
namespace Chamilo\Libraries\Format\Structure;

use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\File\PathBuilder;
use Chamilo\Libraries\Format\Theme\ThemePathBuilder;

/**
 *
 * @package Chamilo\Libraries\Format\Structure
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class AbstractHeaderRenderer implements HeaderRendererInterface
{
    private PageConfiguration $pageConfiguration;

    private PathBuilder $pathBuilder;

    private ThemePathBuilder $themePathBuilder;

    public function __construct(
        PageConfiguration $pageConfiguration, PathBuilder $pathBuilder, ThemePathBuilder $themePathBuilder
    )
    {
        $this->pageConfiguration = $pageConfiguration;
        $this->pathBuilder = $pathBuilder;
        $this->themePathBuilder = $themePathBuilder;
    }

    /**
     * @throws \Exception
     */
    public function render(): string
    {
        $this->addDefaultHeaders();
        $pageConfiguration = $this->getPageConfiguration();

        $html = [];

        $html[] = '<!DOCTYPE html>';
        $html[] = '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . $pageConfiguration->getLanguageCode() .
            '" lang="' . $pageConfiguration->getLanguageCode() . '">';
        $html[] = '<head>';

        $htmlHeaders = $pageConfiguration->getHtmlHeaders();

        foreach ($htmlHeaders as $htmlHeader)
        {
            $html[] = $htmlHeader;
        }

        $html[] = '</head>';

        $html[] = '<body dir="' . $pageConfiguration->getTextDirection() . '">';

        if ($pageConfiguration->getViewMode() != Page::VIEW_MODE_HEADERLESS)
        {
            $html[] = $this->getBanner()->render();
        }

        $classes = $pageConfiguration->getContainerMode();

        if ($pageConfiguration->getViewMode() == Page::VIEW_MODE_HEADERLESS)
        {
            $classes .= ' container-headerless';
        }

        $html[] = '<div class="' . $classes . '">';

        return implode(PHP_EOL, $html);
    }

    /**
     * @throws \Exception
     */
    protected function addDefaultHeaders()
    {
        $pathBuilder = $this->getPathBuilder();
        $themePathBuilder = $this->getThemePathBuilder();
        $pageConfiguration = $this->getPageConfiguration();

        $pageConfiguration->addHtmlHeader('<meta http-equiv="X-UA-Compatible" content="IE=edge">');
        $pageConfiguration->addHtmlHeader('<meta name="viewport" content="width=device-width, initial-scale=1">');
        $pageConfiguration->addHtmlHeader('<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />');

        $cssPath = $pathBuilder->getCssPath('Chamilo/Libraries', true);
        $javascriptPath = $pathBuilder->getJavascriptPath('Chamilo/Libraries', true);

        $pageConfiguration->addCssFile($cssPath . 'cosnics.vendor.bootstrap.min.css');
        $pageConfiguration->addCssFile($cssPath . 'cosnics.vendor.jquery.min.css');
        $pageConfiguration->addCssFile($cssPath . 'cosnics.vendor.min.css');
        $pageConfiguration->addCssFile($cssPath . 'cosnics.common.' . $themePathBuilder->getTheme() . '.min.css');

        $pageConfiguration->addLink($pathBuilder->getBasePath(true), 'top');
        $pageConfiguration->addLink($themePathBuilder->getFavouriteIcon(), 'shortcut icon', null, 'image/x-icon');

        $pageConfiguration->addHtmlHeader(
            '<script>var rootWebPath="' . Path::getInstance()->getBasePath(true) . '";</script>'
        );

        $pageConfiguration->addJavascriptFile($javascriptPath . 'cosnics.vendor.jquery.min.js');
        $pageConfiguration->addJavascriptFile($javascriptPath . 'cosnics.vendor.bootstrap.min.js');
        $pageConfiguration->addJavascriptFile($javascriptPath . 'cosnics.vendor.angular.min.js');
        $pageConfiguration->addJavascriptFile($javascriptPath . 'cosnics.vendor.min.js');
        $pageConfiguration->addJavascriptFile($javascriptPath . 'cosnics.common.min.js');

        $this->addJavascriptCDNFiles();

        $pageConfiguration->addHtmlHeader('<title>' . $pageConfiguration->getTitle() . '</title>');
    }

    /**
     * Adds javascript files from a CDN
     */
    public function addJavascriptCDNFiles()
    {
        $this->getPageConfiguration()->addJavascriptFile(
            'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/MathJax.js?config=TeX-MML-AM_CHTML'
        );
    }

    protected function getBanner(): Banner
    {
        $pageConfiguration = $this->getPageConfiguration();

        return new Banner(
            $pageConfiguration->getApplication(), $pageConfiguration->getViewMode(),
            $pageConfiguration->getContainerMode()
        );
    }

    public function getPageConfiguration(): PageConfiguration
    {
        return $this->pageConfiguration;
    }

    public function setPageConfiguration(PageConfiguration $pageConfiguration): AbstractHeaderRenderer
    {
        $this->pageConfiguration = $pageConfiguration;

        return $this;
    }

    public function getPathBuilder(): PathBuilder
    {
        return $this->pathBuilder;
    }

    public function setPathBuilder(PathBuilder $pathBuilder): AbstractHeaderRenderer
    {
        $this->pathBuilder = $pathBuilder;

        return $this;
    }

    public function getThemePathBuilder(): ThemePathBuilder
    {
        return $this->themePathBuilder;
    }

    public function setThemePathBuilder(ThemePathBuilder $themePathBuilder): AbstractHeaderRenderer
    {
        $this->themePathBuilder = $themePathBuilder;

        return $this;
    }
}
