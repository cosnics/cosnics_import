<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Block\Tool;

use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Template\ToolPublicationsDetailTemplate;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataManager as WeblcmsTrackingDataManager;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Core\Reporting\Viewer\Manager;
use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Translation\Translation;

class LastAccessToToolsUserBlock extends LastAccessToToolsBlock
{

    public function count_data()
    {
        $reporting_data = ToolAccessBlock::count_data();

        $reporting_data->add_row(Translation::get('ViewPublications'));

        $glyph = new FontAwesomeGlyph('chart-pie', array(), Translation::get('ViewPublications'));

        $tool_names = $reporting_data->get_categories();
        foreach ($tool_names as $tool_name)
        {
            $publications = $this->count_tool_publications($tool_name);
            if ($publications > 0)
            {
                $params = $this->get_parent()->get_parameters();
                $params[\Chamilo\Application\Weblcms\Manager::PARAM_TEMPLATE_ID] =
                    ToolPublicationsDetailTemplate::class;
                $params[\Chamilo\Application\Weblcms\Manager::PARAM_USERS] = $this->get_user_id();
                $params[\Chamilo\Application\Weblcms\Tool\Implementation\Reporting\Manager::PARAM_REPORTING_TOOL] =
                    $tool_name;
                $link_pub = '<a href="' . $this->get_parent()->get_url(
                        $params, array(Manager::PARAM_BLOCK_ID)
                    ) . '">' . $glyph->render() . '</a>';

                $reporting_data->add_data_category_row($tool_name, Translation::get('ViewPublications'), $link_pub);
            }
        }

        return $reporting_data;
    }

    /**
     * Returns the condition for the tools publication count
     *
     * @param string $tool_name
     *
     * @return AndCondition
     */
    public function get_tool_publications_condition($tool_name)
    {
        $conditions = array();

        $conditions[] = parent::get_tool_publications_condition($tool_name);

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublication::class, ContentObjectPublication::PROPERTY_PUBLISHER_ID
            ), new StaticConditionVariable($this->get_user_id())
        );

        return new AndCondition($conditions);
    }

    /**
     * Returns the summary data for this course
     *
     * @return \Chamilo\Libraries\Storage\Iterator\DataClassIterator
     */
    public function retrieve_course_summary_data()
    {
        return WeblcmsTrackingDataManager::retrieve_tools_access_summary_data(
            $this->getCourseId(), $this->get_user_id()
        );
    }
}
