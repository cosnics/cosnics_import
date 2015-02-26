<?php
namespace Chamilo\Core\Repository\Builder\Action\Component;

use Chamilo\Core\Repository\Builder\Action\Manager;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRendition;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRenditionImplementation;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Architecture\Exceptions\ParameterNotDefinedException;
use Chamilo\Libraries\Format\Display;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;

/**
 *
 * @author Michael Kyndt
 */
class AttachmentViewerComponent extends Manager
{

    private $action_bar;

    public function run()
    {
        /*
         * Retrieve data and check if it is a valid attachment
         */
        $attachment_id = Request :: get(\Chamilo\Core\Repository\Builder\Action\Manager :: PARAM_ATTACHMENT_ID);
        if (is_null($attachment_id))
        {
            throw new ParameterNotDefinedException(
                \Chamilo\Core\Repository\Builder\Action\Manager :: PARAM_ATTACHMENT_ID);
        }
        $complex_content_object_item = $this->get_parent()->get_selected_complex_content_object_item();
        $reference_content_object_id = $complex_content_object_item->get_ref();
        $reference_content_object = \Chamilo\Core\Repository\Storage\DataManager :: retrieve_content_object(
            $reference_content_object_id);

        if (\Chamilo\Core\Repository\Storage\DataManager :: is_helper_type($reference_content_object->get_type()))
        {
            $reference_content_object_id = $reference_content_object->get_additional_property('reference_id');
            $reference_content_object = \Chamilo\Core\Repository\Storage\DataManager :: retrieve_content_object(
                $reference_content_object_id);
        }

        $attachment = \Chamilo\Core\Repository\Storage\DataManager :: retrieve_content_object($attachment_id);

        if (! $reference_content_object->is_attached_to_or_included_in($attachment_id))
        {
            throw new NotAllowedException();
        }

        /*
         * Render the attachment
         */
        $trail = BreadcrumbTrail :: get_instance();
        $trail->add(
            new Breadcrumb(
                $this->get_url(
                    array(\Chamilo\Core\Repository\Builder\Action\Manager :: PARAM_ATTACHMENT_ID => $attachment_id)),
                Translation :: get('ViewAttachment')));

        $html = array();

        $html[] = Display :: small_header();

        $html[] = '<a href="javascript:history.go(-1)">' .
             Translation :: get('Back', null, Utilities :: COMMON_LIBRARIES) . '</a><br /><br />';

        $html[] = ContentObjectRenditionImplementation :: launch(
            $attachment,
            ContentObjectRendition :: FORMAT_HTML,
            ContentObjectRendition :: VIEW_FULL,
            $this);

        $html[] = Display :: small_footer();

        return implode("\n", $html);
    }
}
