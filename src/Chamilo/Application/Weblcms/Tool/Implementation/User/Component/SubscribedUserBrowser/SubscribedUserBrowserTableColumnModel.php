<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\User\Component\SubscribedUserBrowser;

use Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation;
use Chamilo\Configuration\Configuration;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Format\Table\Column\DataClassPropertyTableColumn;
use Chamilo\Libraries\Format\Table\Extension\RecordTable\RecordTableColumnModel;
use Chamilo\Libraries\Format\Table\Interfaces\TableColumnModelActionsColumnSupport;

/**
 * Table column model for a direct subscribed course user browser table, or
 * users
 * in a direct subscribed group.
 * 
 * @author Stijn Van Hoecke
 * @author Sven Vanpoucke - Hogeschool Gent - Refactoring to RecordTable
 */
class SubscribedUserBrowserTableColumnModel extends RecordTableColumnModel implements 
    TableColumnModelActionsColumnSupport
{
    const DEFAULT_ORDER_COLUMN_INDEX = 1;

    /**
     * **************************************************************************************************************
     * Inherited Functionality *
     * **************************************************************************************************************
     */
    
    /**
     * Initializes the columns for the table
     */
    public function initialize_columns()
    {
        $this->add_column(new DataClassPropertyTableColumn(User::class_name(), User::PROPERTY_USERNAME));

        $showEmail = Configuration::getInstance()->get_setting(array('Chamilo\Core\User', 'show_email_addresses'));

        if($showEmail)
        {
            $this->add_column(new DataClassPropertyTableColumn(User::class_name(), User::PROPERTY_EMAIL));
        }

        $this->add_column(
            new DataClassPropertyTableColumn(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_STATUS));
    }
}
