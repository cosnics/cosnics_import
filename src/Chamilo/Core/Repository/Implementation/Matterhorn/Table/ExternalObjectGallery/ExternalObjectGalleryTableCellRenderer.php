<?php
namespace Chamilo\Core\Repository\Implementation\Matterhorn\Table\ExternalObjectGallery;

use Chamilo\Core\Repository\External\ExternalObjectDisplay;
use Chamilo\Core\Repository\External\Table\ExternalObjectGallery\DefaultExternalObjectGalleryTableCellRenderer;
use Chamilo\Libraries\Utilities\Utilities;

class ExternalObjectGalleryTableCellRenderer extends DefaultExternalObjectGalleryTableCellRenderer
{

    public function render_cell($object)
    {
        $html = array();
        
        $html[] = '<div style="width: 20px; float: right;">';
        $html[] = $this->get_actions($object);
        $html[] = '</div>';
        
        $html[] = '<h3>' . Utilities :: truncate_string($object->get_title(), 25) . ' (' . Utilities :: format_seconds_to_minutes(
            $object->get_duration() / 1000) . ')</h3>';
        $display = ExternalObjectDisplay :: factory($object);
        
        $html[] = '<a href="' . $this->get_component()->get_external_repository_object_viewing_url($object) . '">' . $display->get_preview(
            true) . '</a><br/>';
        $html[] = '<i>' . Utilities :: truncate_string($object->get_description(), 100) . '</i><br/>';
        return implode("\n", $html);
    }
}
