<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Block\Assessment;

use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Block\ToolBlock;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Template\AssessmentAttemptsUserTemplate;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataClass\AssessmentAttempt;
use Chamilo\Application\Weblcms\Manager;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Core\Reporting\ReportingData;
use Chamilo\Core\Reporting\Viewer\Rendition\Block\Type\Html;
use Chamilo\Core\Repository\ContentObject\Assessment\Storage\DataClass\Assessment;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Storage\DataManager;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\DataClassParameters;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\DatetimeUtilities;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * @package application.weblcms.php.reporting.blocks Reporting block with an overiew of the assessments the user has
 *          attempted
 * @author  Joris Willems <joris.willems@gmail.com>
 * @author  Alexander Van Paemel
 */
class CourseUserExerciseInformationBlock extends ToolBlock
{

    public function count_data()
    {
        $reporting_data = new ReportingData();
        $reporting_data->set_rows(
            [
                Translation::get('Title'),
                Translation::get('NumberOfAttempts'),
                Translation::get('LastAttempt'),
                Translation::get('TotalTime'),
                Translation::get('AverageScore'),
                Translation::get('Attempts')
            ]
        );
        $course_id = $this->getCourseId();
        $user_id = $this->get_user_id();

        $glyph = new FontAwesomeGlyph('chart-pie');

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(AssessmentAttempt::class, AssessmentAttempt::PROPERTY_COURSE_ID),
            new StaticConditionVariable($course_id)
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(AssessmentAttempt::class, AssessmentAttempt::PROPERTY_USER_ID),
            new StaticConditionVariable($user_id)
        );
        $condition = new AndCondition($conditions);

        $trackerdata = \Chamilo\Libraries\Storage\DataManager\DataManager::retrieves(
            AssessmentAttempt::class, new DataClassParameters(condition: $condition)
        );

        $exercises = [];
        // $score_count = 0;
        foreach ($trackerdata as $value)
        {
            if ($value->get_status() == AssessmentAttempt::STATUS_COMPLETED)
            {
                $exercises[$value->get_assessment_id()]['score'] += $value->get_total_score();
                $exercises[$value->get_assessment_id()]['score_count'] ++;
            }
            $exercises[$value->get_assessment_id()]['count'] ++;
            $exercises[$value->get_assessment_id()]['total_time'] += $value->get_total_time();

            if ($exercises[$value->get_assessment_id()]['last'] == null ||
                $value->get_start_time() > $exercises[$value->get_assessment_id()]['last'])
            {
                $exercises[$value->get_assessment_id()]['last'] = $value->get_start_time();
            }
        }

        $params = [];
        $params[Application::PARAM_ACTION] = Manager::ACTION_VIEW_COURSE;
        $params[Application::PARAM_CONTEXT] = Manager::CONTEXT;
        $params[Manager::PARAM_COURSE] = $course_id;
        $params[Manager::PARAM_TOOL] = 'assessment';
        $params[Manager::PARAM_TOOL_ACTION] = \Chamilo\Application\Weblcms\Tool\Manager::ACTION_VIEW;

        $filterParams = [\Chamilo\Core\Reporting\Viewer\Manager::PARAM_BLOCK_ID];

        $params_detail = $this->get_parent()->get_parameters();
        $params_detail[Manager::PARAM_TEMPLATE_ID] = AssessmentAttemptsUserTemplate::class;

        $conditions = [];
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublication::class, ContentObjectPublication::PROPERTY_TOOL
            ), new StaticConditionVariable(
                ClassnameUtilities::getInstance()->getClassNameFromNamespace(Assessment::class, true)
            )
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublication::class, ContentObjectPublication::PROPERTY_COURSE_ID
            ), new StaticConditionVariable($course_id)
        );
        $condition = new AndCondition($conditions);
        $publications_resultset =
            \Chamilo\Application\Weblcms\Storage\DataManager::retrieve_content_object_publications(
                $condition
            );

        $key = 0;
        foreach ($publications_resultset as $publication)
        {
            if (!\Chamilo\Application\Weblcms\Storage\DataManager::is_publication_target_user(
                $user_id, $publication[ContentObjectPublication::PROPERTY_ID]
            ))
            {
                continue;
            }
            ++ $key;
            $title = $score = $last = $last = $link = $time = null;
            $count = 0;

            if ($exercises[$publication[ContentObjectPublication::PROPERTY_ID]])
            {
                $value = $exercises[$publication[ContentObjectPublication::PROPERTY_ID]];
                $time = mktime(0, 0, $value['total_time'], 0, 0, 0);
                $time = date('G:i:s', $time);
                $score = $this->get_score_bar($value['score'] / $value['score_count']);
                $last = DatetimeUtilities::getInstance()->formatLocaleDate(
                    Translation::get('DateFormatShort', null, StringUtilities::LIBRARIES) . ', ' .
                    Translation::get('TimeNoSecFormat', null, StringUtilities::LIBRARIES), $value['last']
                );
                $params_detail[Manager::PARAM_PUBLICATION] = $publication[ContentObjectPublication::PROPERTY_ID];
                $link = '<a href="' . $this->get_parent()->get_url($params_detail, $filterParams) . '">' .
                    $glyph->render() . '</a>';
                $count = $value['count'];
            }

            $params[Manager::PARAM_PUBLICATION] = $publication[ContentObjectPublication::PROPERTY_ID];

            $content_object = DataManager::retrieve_by_id(
                ContentObject::class, $publication[ContentObjectPublication::PROPERTY_CONTENT_OBJECT_ID]
            );

            $objectLink = $this->getUrlGenerator()->fromParameters($params, $filterParams);

            $title = '<a href="' . $objectLink . '">' . $content_object->get_title() . '</a>';

            $reporting_data->add_category($key);
            $reporting_data->add_data_category_row($key, Translation::get('Title'), $title);
            $reporting_data->add_data_category_row($key, Translation::get('NumberOfAttempts'), $count);
            $reporting_data->add_data_category_row($key, Translation::get('AverageScore'), $score);
            $reporting_data->add_data_category_row($key, Translation::get('LastAttempt'), $last);
            $reporting_data->add_data_category_row($key, Translation::get('TotalTime'), $time);
            $reporting_data->add_data_category_row($key, Translation::get('Attempts'), $link);
        }
        $reporting_data->hide_categories();

        return $reporting_data;
    }

    public function get_views()
    {
        return [Html::VIEW_TABLE];
    }

    public function retrieve_data()
    {
        return $this->count_data();
    }
}
