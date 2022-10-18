<?php
namespace Chamilo\Application\Weblcms\Rights\Entities\CoursePlatformGroupEntity;

use Chamilo\Core\Group\Storage\DataClass\Group;
use Chamilo\Core\Rights\Editor\Table\LocationEntity\LocationEntityTableColumnModel;
use Chamilo\Libraries\Format\Table\Column\DataClassPropertyTableColumn;

class CoursePlatformGroupEntityTableColumnModel extends LocationEntityTableColumnModel
{

    public function initializeColumns()
    {
        $this->addColumn(new DataClassPropertyTableColumn(Group::class, Group::PROPERTY_NAME));
        $this->addColumn(new DataClassPropertyTableColumn(Group::class, Group::PROPERTY_CODE));
        parent::initializeColumns();
    }
}
