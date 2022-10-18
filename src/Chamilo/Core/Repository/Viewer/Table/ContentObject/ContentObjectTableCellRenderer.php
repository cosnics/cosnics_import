<?php
namespace Chamilo\Core\Repository\Viewer\Table\ContentObject;

use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Format\Structure\Glyph\IdentGlyph;
use Chamilo\Libraries\Format\Table\Column\TableColumn;
use Chamilo\Libraries\Format\Table\Extension\DataClassTable\DataClassTableCellRenderer;
use Chamilo\Libraries\Format\Table\Interfaces\TableCellRendererActionsColumnSupport;
use Chamilo\Libraries\Utilities\DatetimeUtilities;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * This class is a cell renderer for a publication candidate table
 */
class ContentObjectTableCellRenderer extends DataClassTableCellRenderer implements TableCellRendererActionsColumnSupport
{

    public function get_actions($content_object)
    {
        return $this->get_component()->get_default_browser_actions($content_object)->as_html();
    }

    public function renderCell(TableColumn $column, $content_object): string
    {
        switch ($column->get_name())
        {
            case ContentObject::PROPERTY_TYPE :
                return $content_object->get_icon_image(IdentGlyph::SIZE_MINI);
            case ContentObject::PROPERTY_TITLE :
                return StringUtilities::getInstance()->truncate($content_object->get_title(), 50);
            case ContentObject::PROPERTY_DESCRIPTION :
                return StringUtilities::getInstance()->truncate($content_object->get_description(), 50);
            case ContentObject::PROPERTY_MODIFICATION_DATE :
                return DatetimeUtilities::getInstance()->formatLocaleDate(null, $content_object->get_modification_date());
        }

        return parent::renderCell($column, $content_object);
    }
}
