<?php
namespace Chamilo\Core\Repository\Implementation\Youtube\Table\ExternalObject;

use Chamilo\Core\Repository\External\Table\ExternalObject\DefaultExternalObjectTableColumnModel;
use Chamilo\Core\Repository\Implementation\Youtube\ExternalObject;
use Chamilo\Libraries\Format\Table\Column\DataClassPropertyTableColumn;

class ExternalObjectTableColumnModel extends DefaultExternalObjectTableColumnModel
{

    public function initializeColumns()
    {
        $this->addColumn(
            new DataClassPropertyTableColumn(ExternalObject::class, ExternalObject::PROPERTY_TYPE, null, false));
        $this->addColumn(
            new DataClassPropertyTableColumn(ExternalObject::class, ExternalObject::PROPERTY_TITLE, null, false));
        $this->addColumn(
            new DataClassPropertyTableColumn(ExternalObject::class, ExternalObject::PROPERTY_DESCRIPTION, false));
        $this->addColumn(
            new DataClassPropertyTableColumn(ExternalObject::class, ExternalObject::PROPERTY_CREATED, null, false));
    }
}
