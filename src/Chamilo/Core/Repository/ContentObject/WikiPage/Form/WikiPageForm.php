<?php
namespace Chamilo\Core\Repository\ContentObject\WikiPage\Form;

use Chamilo\Core\Repository\ContentObject\WikiPage\Storage\DataClass\WikiPage;
use Chamilo\Core\Repository\Form\ContentObjectForm;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Platform\Session\Request;

/**
 *
 * @package repository.lib.content_object.wiki_page
 */
class WikiPageForm extends ContentObjectForm
{

    public function create_content_object()
    {
        $object = new WikiPage();
        $this->set_content_object($object);
        return parent::create_content_object();
    }

    public function update_content_object()
    {
        $object = $this->get_content_object();

        if ($this->is_version())
        {
            $new_title = $this->exportValue(WikiPage::PROPERTY_TITLE);
            $new_description = $this->exportValue(WikiPage::PROPERTY_DESCRIPTION);

            if ($object->get_title() === $new_title && $object->get_description() === $new_description)
            {
                return true;
            }
        }
        return parent::update_content_object();
    }

    public function setDefaults($defaults = array(), $filter = null)
    {
        $defaults[ContentObject::PROPERTY_TITLE] = Request::get(ContentObject::PROPERTY_TITLE) == null ? null : Request::get(
            ContentObject::PROPERTY_TITLE);

        parent::setDefaults($defaults);
    }
}
