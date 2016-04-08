<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\User\Component;

use Chamilo\Application\Weblcms\Rights\WeblcmsRights;
use Chamilo\Application\Weblcms\Tool\Implementation\User\Component\UnsubscribedGroup\UnsubscribedGroupTable;
use Chamilo\Application\Weblcms\Tool\Implementation\User\Manager;
use Chamilo\Application\Weblcms\Tool\Implementation\User\PlatformgroupMenuRenderer;
use Chamilo\Core\Group\Storage\DataClass\Group;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Format\Structure\ActionBar\Button;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonGroup;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonToolBar;
use Chamilo\Libraries\Format\Structure\ActionBar\Renderer\ButtonToolBarRenderer;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Format\Table\Interfaces\TableSupport;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrieveParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\Condition\NotCondition;
use Chamilo\Libraries\Storage\Query\Condition\OrCondition;
use Chamilo\Libraries\Storage\Query\Condition\PatternMatchCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 * $Id: user_group_subscribe_browser.class.php 216 2009-11-13 14:08:06Z kariboe $
 *
 * @package application.lib.weblcms.tool.user.component
 */
class SubscribeGroupsBrowseSubgroupsComponent extends SubscribeGroupsTabComponent implements TableSupport
{
    /**
     * Renders the content for the tab
     *
     * @return string
     */
    protected function renderTabContent()
    {
        $html = array();

        $html[] = $this->buttonToolbarRenderer->render();

        $table = new UnsubscribedGroupTable($this, $this->get_parameters(), $this->get_table_condition(''));
        $html[] = $table->as_html();

        return implode(PHP_EOL, $html);
    }

    /**
     * Returns the condition for the table
     *
     * @param string $table_class_name
     *
     * @return Condition
     */
    public function get_table_condition($table_class_name)
    {
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(Group:: class_name(), Group :: PROPERTY_PARENT_ID),
            new StaticConditionVariable($this->getGroupId())
        );

        // filter already subscribed groups
        if ($this->subscribedGroups)
        {
            $conditions[] = new NotCondition(
                new InCondition(
                    new PropertyConditionVariable(Group:: class_name(), Group :: PROPERTY_ID),
                    $this->subscribedGroups
                )
            );
        }

        $query = $this->buttonToolbarRenderer->getSearchForm()->getQuery();
        if (isset($query) && $query != '')
        {
            $conditions2[] = new PatternMatchCondition(
                new PropertyConditionVariable(Group:: class_name(), Group :: PROPERTY_NAME),
                '*' . $query . '*'
            );
            $conditions2[] = new PatternMatchCondition(
                new PropertyConditionVariable(Group:: class_name(), Group :: PROPERTY_DESCRIPTION),
                '*' . $query . '*'
            );
            $conditions[] = new OrCondition($conditions2);
        }

        return new AndCondition($conditions);
    }
}
