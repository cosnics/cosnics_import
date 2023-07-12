<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Block;

use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataClass\CourseVisit;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataManager as WeblcmsTrackingDataManager;
use Chamilo\Application\Weblcms\Manager;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Storage\DataManager;
use Chamilo\Core\Reporting\ReportingBlock;
use Chamilo\Core\Reporting\ReportingData;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\DatetimeUtilities;
use Chamilo\Libraries\Utilities\StringUtilities;

abstract class CourseBlock extends ReportingBlock
{
    public const SCORE_TYPE_AVG = 1;

    public const SCORE_TYPE_FIRST = 4;

    public const SCORE_TYPE_LAST = 5;

    public const SCORE_TYPE_MAX = 3;

    public const SCORE_TYPE_MIN = 2;

    /**
     * Adss the rows for the course visit data to the reporting data rows
     *
     * @param ReportingData $reporting_data
     */
    public function add_reporting_data_categories_for_course_visit_data($reporting_data)
    {
        $course_visit_data_titles = $this->get_course_visit_data_titles();
        foreach ($course_visit_data_titles as $title)
        {
            $reporting_data->add_category($title);
        }
    }

    /**
     * Adds the reporting data from a given course visit tracker
     *
     * @param string $category
     * @param ReportingData $reporting_data
     * @param CourseVisit $course_visit
     */
    public function add_reporting_data_from_course_visit_as_category($category, $reporting_data, $course_visit)
    {
        $extracted_course_visit_data = $this->get_course_visit_data_from_course_visit_object($course_visit);
        foreach ($extracted_course_visit_data as $title => $value)
        {
            $reporting_data->add_data_category_row($title, $category, $value);
        }
    }

    /**
     * Adds the reporting data from a given course visit tracker
     *
     * @param mixed $row
     * @param ReportingData $reporting_data
     * @param CourseVisit $course_visit
     */
    public function add_reporting_data_from_course_visit_as_row($row, $reporting_data, $course_visit)
    {
        $extracted_course_visit_data = $this->get_course_visit_data_from_course_visit_object($course_visit);
        foreach ($extracted_course_visit_data as $title => $value)
        {
            $reporting_data->add_data_category_row($row, $title, $value);
        }
    }

    /**
     * Adss the rows for the course visit data to the reporting data rows
     *
     * @param ReportingData $reporting_data
     */
    public function add_reporting_data_rows_for_course_visit_data($reporting_data)
    {
        $course_visit_data_titles = $this->get_course_visit_data_titles();
        foreach ($course_visit_data_titles as $title)
        {
            $reporting_data->add_row($title);
        }
    }

    /**
     * Converts from a seconds based time to an hours based time
     */
    public function convertSecondsToHours(int $seconds): string
    {
        return $seconds ? DatetimeUtilities::getInstance()->convertSecondsToHours($seconds) : '000h 00m 00s';
    }

    /**
     * Counts the publications from a user in a course
     *
     * @param int $user_id
     * @param int $course_id
     *
     * @return int
     */
    public function count_publications_from_user_in_course($user_id, $course_id)
    {
        $publication_conditions = [];

        $publication_conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublication::class, ContentObjectPublication::PROPERTY_PUBLISHER_ID
            ), new StaticConditionVariable($user_id)
        );

        $publication_conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublication::class, ContentObjectPublication::PROPERTY_COURSE_ID
            ), new StaticConditionVariable($course_id)
        );

        $publication_condition = new AndCondition($publication_conditions);

        return DataManager::count_content_object_publications(
            $publication_condition
        );
    }

    /**
     * Format's a timestamp to a date
     *
     * @param int $timestamp
     *
     * @return string
     */
    public function format_date($timestamp)
    {
        if (!is_null($timestamp))
        {
            return DatetimeUtilities::getInstance()->formatLocaleDate(
                Translation::get('DateFormatShort', null, StringUtilities::LIBRARIES) . ', ' .
                Translation::get('TimeNoSecFormat', null, StringUtilities::LIBRARIES), $timestamp
            );
        }
    }

    public function getCourseId()
    {
        return $this->get_parent()->get_parent()->get_parameter(Manager::PARAM_COURSE);
    }

    /**
     * Extracts the course visit data from the course visit object
     *
     * @param $course_visit
     *
     * @return string[]
     */
    public function get_course_visit_data_from_course_visit_object($course_visit)
    {
        if (empty($course_visit))
        {
            return;
        }

        if ($course_visit instanceof CourseVisit)
        {
            $first_access_date = $course_visit->get_first_access_date();
            $last_access_date = $course_visit->get_last_access_date();
            $total_visits = $course_visit->get_total_number_of_access();
            $total_time = $course_visit->get_total_time();
        }
        else
        {
            $first_access_date = $course_visit[CourseVisit::PROPERTY_FIRST_ACCESS_DATE];
            $last_access_date = $course_visit[CourseVisit::PROPERTY_LAST_ACCESS_DATE];
            $total_visits = $course_visit[CourseVisit::PROPERTY_TOTAL_NUMBER_OF_ACCESS];
            $total_time = $course_visit[CourseVisit::PROPERTY_TOTAL_TIME];
        }

        $total_time =
            $total_time ? DatetimeUtilities::getInstance()->convertSecondsToHours($total_time) : '000h 00m 00s';

        $first_access_date =
            $first_access_date ? DatetimeUtilities::getInstance()->formatLocaleDate(null, $first_access_date) : '-';
        $last_access_date =
            $last_access_date ? DatetimeUtilities::getInstance()->formatLocaleDate(null, $last_access_date) : '-';

        return [
            Translation::get('FirstAccess') => $first_access_date,
            Translation::get('LastAccess') => $last_access_date,
            Translation::get('TotalVisits') => $total_visits,
            Translation::get('TotalTime') => $total_time
        ];
    }

    /**
     * Returns the course visit data titles
     *
     * @return string[]
     */
    public function get_course_visit_data_titles()
    {
        return [
            Translation::get('FirstAccess'),
            Translation::get('LastAccess'),
            Translation::get('TotalVisits'),
            Translation::get('TotalTime')
        ];
    }

    /**
     * Returns the course visit data for the given publication
     *
     * @param ContentdObjectPublication $content_object_publication
     *
     * @return CourseVisit
     */
    public function get_course_visit_summary_from_publication($content_object_publication)
    {
        $category_id = $content_object_publication->get_category_id();
        $category_id = $category_id ?: null;

        $tool_id = $this->get_tool_registration($content_object_publication->get_tool())->get_id();

        return WeblcmsTrackingDataManager::retrieve_publication_access_summary_data(
            $content_object_publication->get_course_id(), $tool_id, $category_id, $content_object_publication->get_id(),
            $this->get_user_id()
        );
    }

    public function get_progress_bar($progress)
    {
        $type = $progress == 100 ? 'progress-bar-success' : '';

        $html = [];

        $html[] = '<div class="progress" style="width: 150px; margin-bottom: 0;">';
        $html[] = '<div class="progress-bar ' . $type . ' progress-bar-striped" role="progressbar" aria-valuenow="' .
            $progress . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $progress . '%">';
        $html[] = '<span>' . round($progress, 2) . '%</span>';
        $html[] = '</div>';
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

    public function get_score_bar($score)
    {
        $passingPercentage = $this->getConfigurationConsulter()->getSetting(
            ['Chamilo\Core\Admin', 'passing_percentage']
        );

        $type = ($score >= $passingPercentage) ? 'progress-bar-success' : '';

        $html = [];

        $html[] = '<div class="progress" style="width: 150px; margin-bottom: 0;">';
        $html[] =
            '<div class="progress-bar ' . $type . ' progress-bar-striped" role="progressbar" aria-valuenow="' . $score .
            '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $score . '%">';
        $html[] = '<span>' . round($score, 2) . '%</span>';
        $html[] = '</div>';
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }
}
