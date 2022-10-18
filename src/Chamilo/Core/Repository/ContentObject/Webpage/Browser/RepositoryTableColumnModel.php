<?php
namespace Chamilo\Core\Repository\ContentObject\Webpage\Browser;

use Chamilo\Core\Repository\ContentObject\Webpage\Storage\DataClass\Webpage;
use Chamilo\Libraries\Format\Table\Column\DataClassPropertyTableColumn;

/**
 * Table column model for the repository browser table
 */
class RepositoryTableColumnModel extends \Chamilo\Core\Repository\Table\ContentObject\Table\RepositoryTableColumnModel
{

    public function add_type_columns()
    {
        $this->addColumn(new DataClassPropertyTableColumn(Webpage::class, Webpage::PROPERTY_EXTENSION));
        $this->addColumn(new DataClassPropertyTableColumn(Webpage::class, Webpage::PROPERTY_FILESIZE));
    }
}
