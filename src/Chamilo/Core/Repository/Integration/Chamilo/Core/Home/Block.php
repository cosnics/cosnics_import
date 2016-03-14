<?php
namespace Chamilo\Core\Repository\Integration\Chamilo\Core\Home;

use Chamilo\Core\Repository\Common\Rendition\ContentObjectRendition;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRenditionImplementation;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;

/**
 * Base class for blocks based on a content object.
 *
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author lopprecht
 */
class Block extends \Chamilo\Core\Home\BlockRendition
{
    const CONFIGURATION_OBJECT_ID = 'use_object';

    protected $defaultTitle = '';

    public function __construct($renderer, $block, $defaultTitle = '')
    {
        parent :: __construct($renderer, $block);
        $this->defaultTitle = $defaultTitle ? $defaultTitle : Translation :: get('Object');
    }

    /**
     *
     * @see \Chamilo\Core\Home\Architecture\ConfigurableInterface::getConfigurationVariables()
     */
    public function getConfigurationVariables()
    {
        return array(self :: CONFIGURATION_OBJECT_ID);
    }

    /**
     * The default's title value.
     * That is the title to display when the block is not linked to a content object.
     *
     * @return string
     */
    protected function getDefaultTitle()
    {
        return $this->defaultTitle;
    }

    protected function setDefaultTitle($value)
    {
        $this->defaultTitle = $value;
    }

    /**
     * If the block is linked to an object returns the object id.
     * Otherwise returns 0.
     *
     * @return int
     */
    public function getObjectId()
    {
        return $this->getBlock()->getSetting(self :: CONFIGURATION_OBJECT_ID, 0);
    }

    /**
     * If the block is linked to an object returns it.
     * Otherwise returns null.
     *
     * @return ContentObject
     */
    public function getObject()
    {
        $object_id = $this->getObjectId();

        if ($object_id == 0)
        {
            return null;
        }
        else
        {
            return \Chamilo\Core\Repository\Storage\DataManager :: retrieve_by_id(
                ContentObject :: class_name(),
                $object_id);
        }
    }

    /**
     * Return true if the block is linked to an object.
     * Otherwise returns false.
     *
     * @return bool
     */
    public function isConfigured()
    {
        return $this->getObjectId() != 0;
    }

    public function toHtml($view = '')
    {
        if (! $this->isVisible())
        {
            return '';
        }

        $html = array();
        $html[] = $this->renderHeader();
        $html[] = $this->isConfigured() ? $this->displayContent() : $this->displayEmpty();
        $html[] = $this->renderFooter();
        return implode(PHP_EOL, $html);
    }

    /**
     * Returns the html to display when the block is not configured.
     *
     * @return string
     */
    public function displayEmpty()
    {
        return Translation :: get('ConfigureBlockFirst', null, \Chamilo\Core\Home\Manager :: context());
    }

    /**
     * Returns the html to display when the block is configured.
     *
     * @return string
     */
    public function display_content()
    {
        $content_object = $this->get_object();

        return ContentObjectRenditionImplementation :: launch(
            $content_object,
            ContentObjectRendition :: FORMAT_HTML,
            ContentObjectRendition :: VIEW_DESCRIPTION,
            $this);
    }

    /**
     * Returns the text title to display.
     * That is the content's object title if the block is configured or the default
     * title otherwise;
     *
     * @return string
     */
    public function get_title()
    {
        $content_object = $this->get_object();
        return empty($content_object) ? $this->get_default_title() : $content_object->get_title();
    }

    // BASIC TEMPLATING FUNCTIONS.

    // @TODO: remove that when we move to a templating system
    // @NOTE: could be more efficient to do an include or eval
    private $template_callback_context = array();

    protected function process_template($template, $vars)
    {
        $pattern = '/\{\$[a-zA-Z_][a-zA-Z0-9_]*\}/';
        $this->template_callback_context = $vars;
        $template = preg_replace_callback($pattern, array($this, 'process_template_callback'), $template);
        return $template;
    }

    private function process_template_callback($matches)
    {
        $vars = $this->template_callback_context;
        $name = trim($matches[0], '{$}');
        $result = isset($vars[$name]) ? $vars[$name] : '';
        return $result;
    }

    /**
     * Constructs the attachment url for the given attachment and the current object.
     *
     * @param ContentObject $attachment The attachment for which the url is needed.
     * @return mixed the url, or null if no view right.
     */
    public function get_content_object_display_attachment_url($attachment)
    {
        if (! $this->is_view_attachment_allowed($this->get_object()))
        {
            return null;
        }
        return $this->get_url(
            array(
                \Chamilo\Core\Home\Manager :: PARAM_CONTEXT => \Chamilo\Core\Home\Manager :: context(),
                \Chamilo\Core\Home\Manager :: PARAM_ACTION => \Chamilo\Core\Home\Manager :: ACTION_VIEW_ATTACHMENT,
                \Chamilo\Core\Home\Manager :: PARAM_PARENT_ID => $this->get_object()->get_id(),
                \Chamilo\Core\Home\Manager :: PARAM_OBJECT_ID => $attachment->get_id()));
    }
}
