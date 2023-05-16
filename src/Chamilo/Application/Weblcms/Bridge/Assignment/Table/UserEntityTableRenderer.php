<?php
namespace Chamilo\Application\Weblcms\Bridge\Assignment\Table;

use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Format\Table\Column\TableColumn;
use Chamilo\Libraries\Format\Table\TableResultPosition;

/**
 * @package Chamilo\Application\Weblcms\Bridge\Assignment\Table\Entity
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
abstract class UserEntityTableRenderer extends EntityTableRenderer
{
    protected function initializeColumns()
    {
        $this->addColumn(
            $this->getDataClassPropertyTableColumnFactory()->getColumn(User::class, User::PROPERTY_FIRSTNAME)
        );
        $this->addColumn(
            $this->getDataClassPropertyTableColumnFactory()->getColumn(User::class, User::PROPERTY_LASTNAME)
        );

        parent::initializeColumns();
    }

    protected function renderCell(TableColumn $column, TableResultPosition $resultPosition, $entity): string
    {
        if (($column->get_name() == User::PROPERTY_FIRSTNAME || $column->get_name() == User::PROPERTY_LASTNAME))
        {
            if ($this->canViewEntity($entity))
            {
                return '<a href="' . $this->getEntityUrl($entity) . '">' . $entity[$column->get_name()] . '</a>';
            }
            else
            {
                return $entity[$column->get_name()];
            }
        }

        return parent::renderCell($column, $resultPosition, $entity); // TODO: Change the autogenerated stub
    }

}
