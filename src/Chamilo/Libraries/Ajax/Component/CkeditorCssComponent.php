<?php
namespace Chamilo\Libraries\Ajax\Component;

use Assetic\Asset\AssetCollection;
use Assetic\Filter\CssImportFilter;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Format\Utilities\CssFileAsset;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Protocol\HttpHeader;
use Chamilo\Libraries\Utilities\StringUtilities;
use Chamilo\Libraries\Architecture\Interfaces\NoAuthenticationSupport;

/**
 *
 * @package Chamilo\Libraries\Ajax\Component
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class CkeditorCssComponent extends \Chamilo\Libraries\Ajax\Manager implements NoAuthenticationSupport
{

    public function run()
    {
        $theme = Request :: get('theme');
        $assets = array();

        $contentObjectTypes = \Chamilo\Core\Repository\Storage\DataManager :: get_registered_types();

        $pathUtilities = Path :: getInstance();
        $classnameUtilities = ClassnameUtilities :: getInstance();
        $themeUtilities = new Theme($theme, StringUtilities :: getInstance(), $classnameUtilities, $pathUtilities);

        foreach ($contentObjectTypes as $contentObjectType)
        {
            $relativeEditorPath = 'HtmlEditor/Ckeditor/' . $theme . '.css';
            $namespace = $classnameUtilities->getNamespaceFromClassname($contentObjectType);
            $namespace = $classnameUtilities->getNamespaceParent($namespace, 2);

            $stylesheetPath = $themeUtilities->getCssPath($namespace, false) . $relativeEditorPath;

            if (file_exists($stylesheetPath))
            {
                $asset = new CssFileAsset($pathUtilities, $stylesheetPath);
                $assets[] = $asset;
            }
        }

        HttpHeader :: content_type(HttpHeader :: CONTENT_TYPE_CSS, 'utf-8');

        $asset_collection = new AssetCollection($assets, array(new CssImportFilter()));
        echo $asset_collection->dump();
    }
}