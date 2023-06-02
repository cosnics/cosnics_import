<?php
namespace Chamilo\Core\Install\Component;

use Chamilo\Core\Install\Manager;
use Chamilo\Libraries\Architecture\Interfaces\NoAuthenticationSupport;
use Chamilo\Libraries\Format\Structure\ActionBar\Button;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonToolBar;
use Chamilo\Libraries\Format\Structure\ActionBar\Renderer\ButtonToolBarRenderer;
use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * @package Chamilo\Core\Install\Component
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class LicenseComponent extends Manager implements NoAuthenticationSupport
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $this->checkInstallationAllowed();

        $html = [];

        $html[] = $this->render_header();

        $html[] = '<form class="form">';
        $html[] = '<textarea class="form-control" cols="80" rows="30">' .
            implode('', file(realpath(__DIR__ . '/../../../../../LICENSE'))) . '</textarea>';
        $html[] = '</form>';

        $html[] = '<br />';

        $html[] = $this->getButtons();

        $html[] = $this->render_footer();

        return implode(PHP_EOL, $html);
    }

    /**
     * @return string
     */
    public function getButtons()
    {
        $buttonToolBar = new ButtonToolBar();

        $buttonToolBar->addItem(
            new Button(
                Translation::get('Previous', null, StringUtilities::LIBRARIES), new FontAwesomeGlyph('chevron-left'),
                $this->get_url([self::PARAM_ACTION => self::ACTION_REQUIREMENTS])
            )
        );

        $buttonToolBar->addItem(
            new Button(
                Translation::get('AgreeAndContinue'), new FontAwesomeGlyph('chevron-right'), $this->get_url(
                [
                    self::PARAM_ACTION => self::ACTION_SETTINGS,
                    self::PARAM_LANGUAGE => $this->getSession()->get(self::PARAM_LANGUAGE)
                ]
            ), Button::DISPLAY_ICON_AND_LABEL, null, ['btn-primary']
            )
        );

        $buttonToolbarRenderer = new ButtonToolBarRenderer($buttonToolBar);

        return $buttonToolbarRenderer->render();
    }
}
