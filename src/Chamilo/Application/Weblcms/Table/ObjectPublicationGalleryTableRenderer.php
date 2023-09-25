<?php
namespace Chamilo\Application\Weblcms\Table;

use Chamilo\Application\Weblcms\Renderer\PublicationList\ContentObjectPublicationListRenderer;
use Chamilo\Application\Weblcms\Tool\Manager;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRendition;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRenditionImplementation;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Format\Table\Extension\DataClassGalleryTableRenderer;
use Chamilo\Libraries\Format\Table\FormAction\TableActions;
use Chamilo\Libraries\Format\Table\Interfaces\TableActionsSupport;
use Chamilo\Libraries\Format\Table\Interfaces\TableRowActionsSupport;
use Chamilo\Libraries\Format\Table\TableParameterValues;
use Chamilo\Libraries\Format\Table\TableResultPosition;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @package Chamilo\Application\Weblcms\Table
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class ObjectPublicationGalleryTableRenderer extends DataClassGalleryTableRenderer
    implements TableActionsSupport, TableRowActionsSupport
{
    public const TABLE_IDENTIFIER = Manager::PARAM_PUBLICATION_ID;

    /**
     * @deprecated Temporary solution to allow rendering of DI-based tables in a non-DI context
     */
    protected ContentObjectPublicationListRenderer $contentObjectPublicationListRenderer;

    public function getTableActions(): TableActions
    {
        return $this->contentObjectPublicationListRenderer->get_actions();
    }

    protected function initializeColumns(): void
    {
        $this->addColumn(
            $this->getDataClassPropertyTableColumnFactory()->getColumn(
                ContentObject::class, ContentObject::PROPERTY_TITLE
            )
        );

        $this->addColumn(
            $this->getDataClassPropertyTableColumnFactory()->getColumn(
                ContentObject::class, ContentObject::PROPERTY_DESCRIPTION
            )
        );
    }

    /**
     * @throws \Chamilo\Libraries\Format\Table\Exception\InvalidPageNumberException
     * @throws \QuickformException
     * @throws \TableException
     * @deprecated Temporary solution to allow rendering of DI-based tables in a non-DI context
     */
    public function legacyRender(
        ContentObjectPublicationListRenderer $contentObjectPublicationListRenderer,
        TableParameterValues $parameterValues, ArrayCollection $tableData, ?string $tableName = null
    ): string
    {
        $this->contentObjectPublicationListRenderer = $contentObjectPublicationListRenderer;

        return parent::render($parameterValues, $tableData, $tableName); // TODO: Change the autogenerated stub
    }

    public function renderContent($publication): string
    {
        $object = $this->contentObjectPublicationListRenderer->get_content_object_from_publication($publication);

        $details_url = $this->getUrlGenerator()->fromRequest(
            [
                Manager::PARAM_PUBLICATION_ID => $publication[DataClass::PROPERTY_ID],
                Manager::PARAM_ACTION => Manager::ACTION_VIEW
            ]
        );

        $thumbnail = ContentObjectRenditionImplementation::launch(
            $object, ContentObjectRendition::FORMAT_HTML, ContentObjectRendition::VIEW_THUMBNAIL, $this
        );

        return '<a href="' . $details_url . '">' . $thumbnail . '</a>';
    }

    public function renderTableRowActions(TableResultPosition $resultPosition, $publication): string
    {
        return $this->contentObjectPublicationListRenderer->get_publication_actions($publication, false)->render();
    }

    public function renderTitle($publication): string
    {
        return $this->contentObjectPublicationListRenderer->get_content_object_from_publication($publication)
            ->get_title();
    }
}