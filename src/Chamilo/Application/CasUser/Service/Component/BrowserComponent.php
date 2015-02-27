<?php
namespace Chamilo\Application\CasUser\Service\Component;

use Chamilo\Application\CasUser\Service\Storage\DataClass\Service;
use Chamilo\Application\CasUser\Service\Manager;
use Chamilo\Application\CasUser\Service\Table\Service\ServiceTable;
use Chamilo\Libraries\Format\Structure\ActionBarRenderer;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Format\Table\Interfaces\TableSupport;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\OrCondition;
use Chamilo\Libraries\Storage\Query\Condition\PatternMatchCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;

class BrowserComponent extends Manager implements TableSupport
{

    private $action_bar;

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $trail = BreadcrumbTrail :: get_instance();
        $trail->add_help('cas_user general');

        $this->action_bar = $this->get_action_bar();

        $html = array();

        $html[] = $this->render_header();
        $html[] = '<a name="top"></a>';
        $html[] = $this->action_bar->as_html();
        $html[] = '<div id="action_bar_browser">';
        $table = new ServiceTable($this);
        $html[] = $table->as_html();
        $html[] = '</div>';
        $html[] = $this->render_footer();

        return implode(PHP_EOL, $html);
    }

    public function get_table_condition($object_table_class_name)
    {
        $user = $this->get_user();
        $query = $this->action_bar->get_query();
        $conditions = array();

        if (isset($query) && $query != '')
        {
            $query_conditions = array();
            $query_conditions[] = new PatternMatchCondition(
                new PropertyConditionVariable(Service :: class_name(), Service :: PROPERTY_NAME),
                '*' . $query . '*');
            $query_conditions[] = new PatternMatchCondition(
                new PropertyConditionVariable(Service :: class_name(), Service :: PROPERTY_DESCRIPTION),
                '*' . $query . '*');
            $query_conditions[] = new PatternMatchCondition(
                new PropertyConditionVariable(Service :: class_name(), Service :: PROPERTY_SERVICE_ID),
                '*' . $query . '*');
            $conditions[] = new OrCondition($query_conditions);
        }

        if (count($conditions) > 0)
        {
            return new AndCondition($conditions);
        }
        else
        {
            return null;
        }
    }

    public function get_action_bar()
    {
        if (! isset($this->action_bar))
        {
            $this->action_bar = new ActionBarRenderer(ActionBarRenderer :: TYPE_HORIZONTAL);
            $this->action_bar->set_search_url($this->get_url());

            if ($this->get_user()->is_platform_admin())
            {
                $this->action_bar->add_common_action(
                    new ToolbarItem(
                        Translation :: get('CreateService'),
                        Theme :: getInstance()->getCommonImagesPath() . 'action_create.png',
                        $this->get_url(array(Manager :: PARAM_ACTION => Manager :: ACTION_CREATE))));
            }
        }
        return $this->action_bar;
    }
}
