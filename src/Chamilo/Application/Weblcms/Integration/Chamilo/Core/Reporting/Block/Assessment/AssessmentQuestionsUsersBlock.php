<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Block\Assessment;

use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Block\ToolBlock;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataClass\AssessmentAttempt;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataClass\QuestionAttempt;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Storage\DataManager;
use Chamilo\Core\Reporting\ReportingData;
use Chamilo\Core\Reporting\Viewer\Rendition\Block\Type\Html;
use Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem;
use Chamilo\Libraries\Storage\Parameters\RetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\OrderBy;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Translation\Translation;

/**
 * @package application.weblcms.php.reporting.blocks Reporting block with an overview of scores of each assessment
 *          question per user
 * @author  Joris Willems <joris.willems@gmail.com>
 * @author  Alexander Van Paemel
 * @author  Anthony Hurst (Hogeschool Gent)
 */
class AssessmentQuestionsUsersBlock extends ToolBlock
{

    private static $COLUMN_NAME;

    /**
     * Cache of reporting data to prevent the code being run twice.
     *
     * @var \reporting\ReportingData
     */
    private $reporting_data;

    /**
     * Instatiates the column headers.
     *
     * @param type $parent Pass-through variable. Please refer to parent class(es) for more details. &param type
     *                     $vertical Pass-through variable. Please refer to parent class(es) for more details.
     */
    public function __construct($parent, $vertical)
    {
        self::$COLUMN_NAME = Translation::get('Name');
        parent::__construct($parent, $vertical);
    }

    /**
     * Obtains the score for each question.
     *
     * @param array $assessment_attempts_tracker_ids The ids of the assessment attempts trackers whose question attempts
     *                                               trackers are to be retrieved
     *
     * @return array Format $question_id => $question_score.
     * @author Anthony Hurst (Hogeschool Gent)
     */
    private function collate_question_attempts_trackers($assessment_attempts_tracker_ids)
    {
        // Retrieve all the question attempts trackers of a single user ordered by the question id.
        $condition = new InCondition(
            new PropertyConditionVariable(
                QuestionAttempt::class, QuestionAttempt::PROPERTY_ASSESSMENT_ATTEMPT_ID
            ), $assessment_attempts_tracker_ids
        );

        $order_by = OrderBy::generate(QuestionAttempt::class, QuestionAttempt::PROPERTY_QUESTION_COMPLEX_ID);

        $question_attempts_trackers = DataManager::retrieves(
            QuestionAttempt::class, new RetrievesParameters($condition, null, null, $order_by)
        );

        $user_question_statistics = [];
        if (count($question_attempts_trackers) > 0)
        {
            $current_question_attempts_trackers = [];
            $current_question_id = reset($question_attempts_trackers)->get_question_complex_id();

            // Aggregate all the question attempts trackers for a single question to calculate the score to
            // be displayed.
            foreach ($question_attempts_trackers as $question_attempts_tracker)
            {
                // If the question id changes, calculate the score for the current question and move on to the
                // next question.
                if ($question_attempts_tracker->get_question_complex_id() != $current_question_id)
                {
                    $user_question_statistics[$current_question_id] = $this->get_score(
                        $current_question_attempts_trackers
                    );
                    $current_question_attempts_trackers = [];
                    $current_question_id = $question_attempts_tracker->get_question_complex_id();
                }
                $current_question_attempts_trackers[] = $question_attempts_tracker;
            }
            // Catch any remainders in the array that won't be caught by the if. ($question_attempts_tracker is null,
            // preventing a last pass through)
            $user_question_statistics[$current_question_id] = $this->get_score($current_question_attempts_trackers);
        }

        return $user_question_statistics;
    }

    public function count_data()
    {
        return $this->retrieve_data();
    }

    private function get_score($attempts)
    {
        $score_type = $this->getRequest()->request->get('sel', $this->getRequest()->query->get('sel'));
        $score = null;

        switch ($score_type)
        {
            case self::SCORE_TYPE_AVG :
                foreach ($attempts as $attempt)
                {
                    $score += $attempt->get_score();
                }

                return round($score / count($attempts), 2);
            case self::SCORE_TYPE_MIN :
                foreach ($attempts as $attempt)
                {
                    if (is_null($score) || $attempt->get_score() < $score)
                    {
                        $score = $attempt->get_score();
                    }
                }

                return $score;
            case self::SCORE_TYPE_MAX :
                foreach ($attempts as $attempt)
                {
                    if (is_null($score) || $attempt->get_score() > $score)
                    {
                        $score = $attempt->get_score();
                    }
                }

                return $score;
            case self::SCORE_TYPE_FIRST :
                $date = null;
                foreach ($attempts as $attempt)
                {
                    return $attempt->get_score();
                }
            case self::SCORE_TYPE_LAST :
                $date = null;
                foreach ($attempts as $attempt)
                {
                    $score = $attempt->get_score();
                }

                return $score;
            default :
                foreach ($attempts as $attempt)
                {
                    $score += $attempt->get_score();
                }

                return round($score / count($attempts), 2);
        }
    }

    public function get_views()
    {
        return [Html::VIEW_TABLE];
    }

    /**
     * Compiles the statistics to be displayed in the reporting block.
     *
     * @return \reporting\ReportingData The reporting data to be displayed.
     * @author Anthony Hurst (Hogeschool Gent)
     */
    public function retrieve_data()
    {
        if (!is_null($this->reporting_data))
        {
            return $this->reporting_data;
        }
        $this->reporting_data = new ReportingData();

        $publication = DataManager::retrieve_by_id(
            ContentObjectPublication::class, $this->getPublicationId()
        );

        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem::class, ComplexContentObjectItem::PROPERTY_PARENT
            ), new StaticConditionVariable($publication->get_content_object_id())
        );
        $complex_questions = \Chamilo\Core\Repository\Storage\DataManager::retrieve_complex_content_object_items(
            ComplexContentObjectItem::class, new RetrievesParameters($condition)
        );

        /**
         * @var $questions Defines column headers against the question id.
         */
        $question_headers = [];
        $question_weights = [];
        // Defines the column headers in the reporting block.
        $question_headers[- 1] = self::$COLUMN_NAME;
        $question_headers[- 2] = Translation::get('OfficialCode');

        $vertical_headers = count($complex_questions) > 5;
        foreach ($complex_questions as $complex_question)
        {
            $question_header = $vertical_headers ? substr($complex_question->get_ref_object()->get_title(), 0, 14) :
                $complex_question->get_ref_object()->get_title();
            $question_headers[$complex_question->get_id()] = '<div id="' . $complex_question->get_id() . '" title="' .
                htmlentities($complex_question->get_ref_object()->get_title()) . '">' . $question_header . '</div>';
            $question_weights[$complex_question->get_id()] = $complex_question->get_weight();
        }

        // Defines the row categories in the reporting block.
        $this->reporting_data->set_rows($question_headers);

        $users = DataManager::retrieve_publication_target_users(
            $this->getPublicationId(), $this->getCourseId()
        );

        foreach ($users as $user)
        {
            $this->reporting_data->add_category($user->get_id());
            $this->reporting_data->add_data_category_row($user->get_id(), self::$COLUMN_NAME, $user->get_fullname());

            $this->reporting_data->add_data_category_row(
                $user->get_id(), Translation::get('OfficialCode'), $user->get_official_code()
            );
        }
        // Retrieve all the assessment attempts trackers for the current assessment ordered by the user id.
        $condition = new EqualityCondition(
            new PropertyConditionVariable(AssessmentAttempt::class, AssessmentAttempt::PROPERTY_ASSESSMENT_ID),
            new StaticConditionVariable($publication->get_id())
        );

        $order_by = OrderBy::generate(AssessmentAttempt::class, AssessmentAttempt::PROPERTY_USER_ID);

        $assessment_attempts_trackers = \Chamilo\Libraries\Storage\DataManager\DataManager::retrieves(
            AssessmentAttempt::class, new RetrievesParameters($condition, null, null, $order_by)
        );

        $user_question_statistics = [];
        if (count($assessment_attempts_trackers) > 0)
        {
            $assessment_attempts_tracker_ids = [];
            $current_user_id = reset($assessment_attempts_trackers)->get_user_id();

            // Aggregate all the assessment attempts trackers for a single user to retrieve the user's
            // question attempts trackers in one database transaction.
            foreach ($assessment_attempts_trackers as $assessment_attempts_tracker)
            {
                if ($assessment_attempts_tracker->get_status() == AssessmentAttempt::STATUS_NOT_COMPLETED)
                {
                    continue;
                }

                // If the user id changes, calculate the user's statistics and move on to the next user.
                if ($assessment_attempts_tracker->get_user_id() != $current_user_id)
                {
                    $user_question_statistics[$current_user_id] = $this->collate_question_attempts_trackers(
                        $assessment_attempts_tracker_ids
                    );

                    $assessment_attempts_tracker_ids = [];
                    $current_user_id = $assessment_attempts_tracker->get_user_id();
                }
                $assessment_attempts_tracker_ids[] = $assessment_attempts_tracker->get_id();
            }
            // Catch any remainders in the array that won't be caught by the if.
            // ($assessment_attempts_tracker is null, preventing a last pass through)
            $user_question_statistics[$current_user_id] = $this->collate_question_attempts_trackers(
                $assessment_attempts_tracker_ids
            );
        }

        $passingPercentage = $this->getConfigurationConsulter()->getSetting(
            ['Chamilo\Core\Admin', 'passing_percentage']
        );

        foreach ($user_question_statistics as $user_id => $question_statistics)
        {
            foreach ($question_statistics as $question_id => $score)
            {
                if (!is_null($score))
                {
                    if (($score / $question_weights[$question_id]) >= $passingPercentage / 100)
                    {
                        $score_html =
                            '<span style="color:green">' . $score . '/' . $question_weights[$question_id] . '</span>';
                    }
                    else
                    {
                        $score_html =
                            '<span style="color:red">' . $score . '/' . $question_weights[$question_id] . '</span>';
                    }
                    $this->reporting_data->add_data_category_row(
                        $user_id, $question_headers[$question_id], $score_html
                    );
                }
            }
        }
        $this->reporting_data->hide_categories();

        return $this->reporting_data;
    }
}
