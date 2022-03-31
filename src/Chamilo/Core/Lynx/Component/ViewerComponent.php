<?php
namespace Chamilo\Core\Lynx\Component;

use Chamilo\Configuration\Configuration;
use Chamilo\Configuration\Storage\DataClass\Registration;
use Chamilo\Core\Lynx\Manager;
use Chamilo\Core\Lynx\PackageDisplay;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\Format\Structure\ActionBar\Button;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonGroup;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonToolBar;
use Chamilo\Libraries\Format\Structure\ActionBar\Renderer\ButtonToolBarRenderer;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\Utilities;

class ViewerComponent extends Manager implements DelegateComponent
{

    /**
     *
     * @var ButtonToolBarRenderer
     */
    private $buttonToolbarRenderer;

    private $context;

    private $registration;

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $this->context = Request::get(self::PARAM_CONTEXT);
        $this->registration = Configuration::registration($this->context);

        BreadcrumbTrail::getInstance()->add(
            new Breadcrumb(
                null, Translation::get(
                'ViewingPackage', array('PACKAGE' => Translation::get('TypeName', null, $this->context))
            )
            )
        );
        $this->buttonToolbarRenderer = $this->getButtonToolbarRenderer();

        $display = new PackageDisplay($this);

        $html = [];

        $html[] = $this->render_header();
        $html[] = $this->buttonToolbarRenderer->render();
        $html[] = $display->render();
        $html[] = $this->render_footer();

        return implode(PHP_EOL, $html);
    }

    public function getButtonToolbarRenderer()
    {
        if (!isset($this->buttonToolbarRenderer))
        {
            $buttonToolbar = new ButtonToolBar();
            $commonActions = new ButtonGroup();
            $registration = $this->get_registration();

            if (!empty($registration))
            {
                if ($registration[Registration::PROPERTY_STATUS])
                {
                    if (!is_subclass_of(
                        $registration[Registration::PROPERTY_CONTEXT] . '\Deactivator',
                        'Chamilo\Configuration\Package\NotAllowed'
                    ))
                    {
                        $commonActions->addButton(
                            new Button(
                                Translation::get('Deactivate', [], Utilities::COMMON_LIBRARIES),
                                new FontAwesomeGlyph('pause-circle', [], null, 'fas'), $this->get_url(
                                array(
                                    self::PARAM_ACTION => self::ACTION_DEACTIVATE, self::PARAM_CONTEXT => $this->context
                                )
                            )
                            )
                        );
                    }
                }
                else
                {
                    if (!is_subclass_of(
                        $registration[Registration::PROPERTY_CONTEXT] . '\Activator',
                        'Chamilo\Configuration\Package\NotAllowed'
                    ))
                    {
                        $commonActions->addButton(
                            new Button(
                                Translation::get('Activate', [], Utilities::COMMON_LIBRARIES),
                                new FontAwesomeGlyph('play-circle', [], null, 'fas'), $this->get_url(
                                array(
                                    self::PARAM_ACTION => self::ACTION_ACTIVATE, self::PARAM_CONTEXT => $this->context
                                )
                            )
                            )
                        );
                    }
                }
            }
            else
            {
                $commonActions->addButton(
                    new Button(
                        Translation::get('Install', [], Utilities::COMMON_LIBRARIES),
                        new FontAwesomeGlyph('box', [], null, 'fas'), $this->get_url(
                        array(self::PARAM_ACTION => self::ACTION_INSTALL, self::PARAM_CONTEXT => $this->context)
                    ), ToolbarItem::DISPLAY_ICON_AND_LABEL, true
                    )
                );
            }

            $buttonToolbar->addButtonGroup($commonActions);

            $this->buttonToolbarRenderer = new ButtonToolBarRenderer($buttonToolbar);
        }

        return $this->buttonToolbarRenderer;
    }

    public function get_context()
    {
        return $this->context;
    }

    public function get_registration()
    {
        return $this->registration;
    }
}