<?php
namespace Chamilo\Core\User\Integration\Chamilo\Core\Menu\Renderer\Item;

use Chamilo\Core\Menu\Storage\DataClass\Item;
use Chamilo\Core\User\Manager;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;

/**
 * @package Chamilo\Core\User\Integration\Chamilo\Core\Menu\Renderer\ItemRenderer
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class AccountItemRenderer extends MenuItemRenderer
{
    /**
     * @return \Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph
     */
    public function getGlyph()
    {
        return new FontAwesomeGlyph('user', array('fa-2x'), null, 'fas');
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $redirect = new Redirect(
            array(
                Application::PARAM_CONTEXT => Manager::context(),
                Application::PARAM_ACTION => Manager::ACTION_VIEW_ACCOUNT
            )
        );

        return $redirect->getUrl();
    }

    /**
     * @param \Chamilo\Core\Menu\Storage\DataClass\Item $item
     *
     * @return boolean
     */
    public function isSelected(Item $item)
    {
        $currentContext = $this->getRequest()->query->get(Application::PARAM_CONTEXT);
        $currentAction = $this->getRequest()->query->get(Manager::PARAM_ACTION);

        return $currentContext == Manager::package() && $currentAction == Manager::ACTION_VIEW_ACCOUNT;
    }

    /**
     * @param \Chamilo\Core\Menu\Storage\DataClass\Item $item
     *
     * @return string
     */
    public function renderTitle(Item $item)
    {
        return $this->getTranslator()->trans('MyAccount', [], 'Chamilo\Core\User');
    }
}